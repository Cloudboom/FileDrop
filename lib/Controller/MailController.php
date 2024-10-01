<?php

declare(strict_types=1);

namespace OCA\FileDrop\Controller;

use OCP\IRequest; 
use OCP\Files\IRootFolder; 
use OCP\Mail\IMailer;
use OCP\Files\Folder;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserManager; 
use OCP\AppFramework\Controller; 
use OCP\IUser; 
use OCP\Constants; 
use OCP\Share; 
use OCP\Share\IManager;
use OCP\IURLGenerator; 
use OCP\SystemTag\ISystemTagManager; 
use OCP\SystemTag\ISystemTagObjectMapper; 
use OCP\SystemTag\TagNotFoundException; 
use OCP\IDateTimeFormatter; 
use OCP\ILogger; 
use OCP\AppFramework\Utility\ITimeFactory; 
use OCP\AppFramework\Http\RedirectResponse; 

class MailController extends Controller {

	private $rootFolder;
	private $mailer;
	private $userManager;
	private $userId;
	private $shareManager;	
	private $urlGenerator;
	private $tagManager;
	private $objectMapper;
	private $dateTimeFormatter;
	private $logger;
	private $timeFactory;

	public function __construct(string $appName,
								IRequest $request,
								IRootFolder $rootFolder,
								IMailer $mailer,
								IUserManager $userManager,
								string $userId,
								IManager $shareManager,
								IURLGenerator $urlGenerator,
								ISystemTagManager $tagManager,
								ISystemTagObjectMapper $objectMapper, 
								IDateTimeFormatter $dateTimeFormatter,
								ILogger $logger,
								ITimeFactory $timeFactory
								) {
		parent::__construct($appName, $request);

		$this->rootFolder = $rootFolder;
		$this->mailer = $mailer;
		$this->userManager = $userManager;
		$this->userId = $userId;
		$this->shareManager = $shareManager;	
		$this->urlGenerator = $urlGenerator;
		$this->tagManager = $tagManager;
		$this->objectMapper = $objectMapper;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
								}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 *
	 * Main function that manages the upload
	 */
	public function processUpload(){
		$uploadFolder = $this->prepareUploadEnvironment();
		$this->addRetentionTag($uploadFolder);
		$countedFiles = $this->storeFiles($uploadFolder); 
		$link = $this->getShareLink($uploadFolder);
		$outputArray = $this->sendEmail($link, $countedFiles);
		//hinzufügen von Errormeldungen zur Ausgabe.
		$outputArray['error'] = '';
		// aufrufen von templates/successs.php
		return new TemplateResponse('FileDrop', 'success', $outputArray);  
	}

	/**
	* Checks whether the FileDrop folder already exists in users files
	*/
	private function prepareUploadEnvironment(){
		$userFolder = \OC::$server->getUserFolder();
		//holendes Pfades zum Homedir 
		$userFolder->getPath();
		//prüfen ob schon ein cLoad-Ordner angelegt ist
		if (! $userFolder->nodeExists('FileDrop')){ 
			$userFolder->newFolder('FileDrop');
		}
		//generieren des Unix-Timestamps
		$timestamp = $this->timeFactory->getTime(); 
		$uploadFolder = $userFolder->newFolder('FileDrop/' . $timestamp);
		return $uploadFolder;
	}

	/**
	* Checks whether the filedrop_retention tag is present and adds it to the file or folder
	*/
	private function addRetentionTag($uploadFolder){  
		try{
			//holen der Daten des Tags cLoad_retention; false, false für nicht sichtbar und nicht änderbar
			$tag = $this->tagManager->getTag('filedrop_retention', false, false);
			//dem übergebenen Ordner oder File das Tag hinzufügen
			$this->objectMapper->assignTags((string) $uploadFolder->getId(),'files',$tag->getId());
		}
		catch (TagNotFoundException $e) {
			$this->logger->logException($e, ['app' => 'FileDrop', 'message' => 'no Tag']); 
			return false;
		}
		return true;
	}

	/**
	* Stores the uploaded data in the previously created folder
	*/
	private function storeFiles($uploadFolder){
		$files = $this->request->files; 
		$dataCount = 0;
		if (count($files['data']['name']) == 1 && $files['data']['name'][0] == '') { 
			$noData = 'No data selected';
			$this->logger->info($noData, ['app' => 'FileDrop']);
		}
		else{
			for ($i = 0; $i < count($files['data']['name']); $i++){ 
				//counter fürs E-Mail Template
				$dataCount ++; 
				$fileName = $files['data']['name'][$i];
				$tmpName = $files['data']['tmp_name'][$i];
				$file = $uploadFolder->newFile($fileName);
				//ablage der Daten im Ordner
				$file->putContent(file_get_contents($tmpName));	 
				$this->addRetentionTag($file);
			}
		}
		return $dataCount;
	}
	
	/**
	* Sets the file to may be shared and grabs the generated link
	*/
	private function getShareLink($uploadFolder){
		$share = $this->shareManager->newShare();
		$share->setNode($uploadFolder); 
		$share->setShareType(Share::SHARE_TYPE_LINK); 
		$share->setPermissions(Constants::PERMISSION_READ); 
		$share->setSharedBy($this->userId); 
		$password = $this->request->password; 
		if (!empty($password)){
			$share->setPassword($password); 
		}
		$createdShare = $this->shareManager->createShare($share); 
		$absoluteRoute = 'files_sharing.sharecontroller.showShare';
		$link = $this->urlGenerator->linkToRouteAbsolute($absoluteRoute,['token' => $createdShare->getToken()]); 
		return $link;
	}

	/**
	* Is the management class for the email creation process
	*/
	private function sendEmail($link, $countedFiles){
		$emailAdress = $this->request->email; 
			$fromEmail = $this->getUser()->getEMailAddress(); 
		if (!$fromEmail) {
			$fromEmail = 'filedrop@no-reply.com'; 
		}
		$emailTemplate = $this->buildEmailTemplate($link, $countedFiles, $fromEmail);
		$emailList = preg_split('/[,;]/', $emailAdress); 
		$validEmails = []; 
		$invalidEmails = []; 
		foreach ($emailList as $singleEmail){ 
			$singleEmail = trim($singleEmail);
			if (filter_var($singleEmail, FILTER_VALIDATE_EMAIL)) { 
				$validEmails[] = $singleEmail;
			} else {
				$invalidEmails[] = $singleEmail;
			}	
		}
		$subject = $this->request->subject; 
		try {
			$email = $this->mailer->createMessage();  		
			$email->setFrom([$fromEmail]);
			$email->setSubject($subject);
			$email->setPlainBody($emailTemplate->renderText());
			$email->setHtmlBody($emailTemplate->renderHtml());
			
			foreach ($validEmails as $singleEmail){  
				$email->setTo([$singleEmail]);
				$this->mailer->send($email);
			}
		} 
		catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'FileDrop']);
		}
		$returnArray = ['validEmails' => $validEmails, 'invalidEmails' => $invalidEmails]; 	
		return $returnArray;
	}

	/**
	* Retrieves the ID of the current user
	*/
	private function getUser(){
		$user = $this->userManager->get($this->userId); 
		if (!$user instanceof IUser) {
			return array();
		}
		return $user;
	}

	/**
	* The e-mail template was moved here
	*/
    private function buildEmailTemplate($link, $countedFiles, $fromEmail){
		$message = $this->request->message; 

		if ($countedFiles === 1){ 
			$filledTemplate = $this->mailer->createEMailTemplate('filedrop.Sendlink');
			$filledTemplate->addHeader();
			$filledTemplate->addHeading($fromEmail . ' has provided a file for you via FileDrop.',''); 
			$filledTemplate->addBodyText(htmlspecialchars('The following message has been left for you:'), 'The following message has been left for you:');
			$filledTemplate->addBodyText(htmlspecialchars($message), $message);
			$filledTemplate->addBodyButton('To your file', $link,'');
			$filledTemplate->addFooter('Kind regards'. "<br/>" . 'Your FileDrop-Service' );
		}
		else{ 
			$filledTemplate = $this->mailer->createEMailTemplate('filedrop.Sendlink');
			$filledTemplate->addHeader();
			$filledTemplate->addHeading($fromEmail . ' has provided several files for you via FileDrop.',''); 
			$filledTemplate->addBodyText(htmlspecialchars('The following message has been left for you:'), 'The following message has been left for you:');
			$filledTemplate->addBodyText(htmlspecialchars($message), $message);
			$filledTemplate->addBodyButton('To your files', $link,'');
			$filledTemplate->addFooter('Kind regards'. "<br/>" . 'Your FileDrop-Service' );
		}
		return $filledTemplate;
	}	
}