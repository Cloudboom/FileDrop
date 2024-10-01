<?php
namespace OCA\FileDrop\Controller;

//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/IRequest.html
use OCP\IRequest; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Files/IRootFolder.html
use OCP\Files\IRootFolder; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Mail/IMailer.html
use OCP\Mail\IMailer;
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Files/Folder.html
use OCP\Files\Folder;
//@see https://docs.nextcloud.com/server/13/developer_manual/api/OCP/AppFramework/Http/TemplateResponse.html
use OCP\AppFramework\Http\TemplateResponse;
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/IUserManager.html
use OCP\IUserManager; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/AppFramework/Controller.html
use OCP\AppFramework\Controller; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Remote/IUser.html
use OCP\IUser; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Constants.html
use OCP\Constants; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Share/IShare.html
use OCP\Share; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/Share/IManager.html
use OCP\Share\IManager;
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/IURLGenerator.html
use OCP\IURLGenerator; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/SystemTag/ISystemTagManager.html
use OCP\SystemTag\ISystemTagManager; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/SystemTag/ISystemTagObjectMapper.html
use OCP\SystemTag\ISystemTagObjectMapper; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/SystemTag/TagNotFoundException.html
use OCP\SystemTag\TagNotFoundException; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/IDateTimeFormatter.html
use OCP\IDateTimeFormatter; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/ILogger.html
use OCP\ILogger; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/AppFramework/Utility/ITimeFactory.html
use OCP\AppFramework\Utility\ITimeFactory; 
//@see https://docs.nextcloud.com/server/14/developer_manual/api/OCP/AppFramework/Http/RedirectResponse.html
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
	 * Ist die Main Funktion und Verwaltet den Upload
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
		return new TemplateResponse('filedrop', 'success', $outputArray);  
	}

	/**
	* Prüft ob der Ordner FileDrop schon in der Ablage vorhanden ist
	*/
	private function prepareUploadEnvironment(){
		$userFolder = \OC::$server->getUserFolder();
		//holendes Pfades zum Homedir 
		$userFolder->getPath();
		//prüfen ob schon ein FileDrop-Ordner angelegt ist
		if (! $userFolder->nodeExists('FileDrop')){ 
			$userFolder->newFolder('FileDrop');
		}
		//generieren des Unix-Timestamps
		$timestamp = $this->timeFactory->getTime(); 
		$uploadFolder = $userFolder->newFolder('FileDrop/' . $timestamp);
		return $uploadFolder;
	}

	/**
	* Prüft ob das Tag FileDrop_retention vorhanden ist und fügt es der Datei oder dem Ordner an
	*/
	private function addRetentionTag($uploadFolder){  
		try{
			//holen der Daten des Tags FileDrop_retention; false, false für nicht sichtbar und nicht änderbar
			$tag = $this->tagManager->getTag('FileDrop_retention', false, false);
			//dem übergebenen Ordner oder File das Tag hinzufügen
			$this->objectMapper->assignTags((string) $uploadFolder->getId(),'files',$tag->getId());
		}
		catch (TagNotFoundException $e) {
			//ablager der Exception im Log
			$this->logger->logException($e, ['app' => 'FileDrop', 'message' => 'no Tag']); 
			return false;
		}
		return true;
	}

	/**
	* Legt die hochgeladenen Daten im vorher erstellten Ordner ab
	*/
	private function storeFiles($uploadFolder){
		$files = $this->request->files; //abruf der ausgewählten Daten
		$dataCount = 0;
		//Prüfung ob keine Datei angegeben wurde
		if (count($files['data']['name']) == 1 && $files['data']['name'][0] == '') { 
			$noData = 'keine Datei ausgewählt';
			//ablagern der Exception im Log
			$this->logger->info($noData, ['app' => 'FileDrop']);
		}
		else{
			//für jede angegebene Datei
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
	* Setzt die Datei auf darf geteilt werden und hohlt sich den generierten Link
	*/
	private function getShareLink($uploadFolder){
		$share = $this->shareManager->newShare();
		//Aktuelle einstellungen des Ordners hohlen
		$share->setNode($uploadFolder); 
		//Ordner auf Teilbar über Link setzen
		$share->setShareType(Share::SHARE_TYPE_LINK); 
		//Zugriff über den Link auf lesend setzen
		$share->setPermissions(Constants::PERMISSION_READ); 
		$share->setSharedBy($this->userId); 
		//holen des Passwortfelds
		$password = $this->request->password; 
		if (!empty($password)){
			//wenn Passwortfeld gefüllt, Passwort vergeben
			$share->setPassword($password); 
		}
		//Speichern der neuen Einstellungen
		$createdShare = $this->shareManager->createShare($share); 
		//generieren des Links
		$absoluteRoute = 'files_sharing.sharecontroller.showShare';
		$link = $this->urlGenerator->linkToRouteAbsolute($absoluteRoute,['token' => $createdShare->getToken()]); 
		return $link;
	}

	/**
	* Ist die Verwaltungsklasse für den Vorgang der E-Mailerstellung
	*/
	private function sendEmail($link, $countedFiles){
		//holen der gegebenen E-Mail Adressen
		$emailAdress = $this->request->email; 
			//im System hinterleget E-Mail Adresse des Users nehmen
			$fromEmail = $this->getUser()->getEMailAddress(); 
		if (!$fromEmail) {
			//vergabe der standard E-Mail wenn keine E-Mail hinerlegt war
			$fromEmail = 'filedrop@no-reply.com'; 
		}
		$emailTemplate = $this->buildEmailTemplate($link, $countedFiles, $fromEmail);
		//splitten der Mails nach , und ;
		$emailList = preg_split('/[,;]/', $emailAdress); 
		// Array für alle validen E-Mails
		$validEmails = []; 
		//Array für alle invaliden E-Mails
		$invalidEmails = []; 
		//sortieren nach validen und invaliden E-Mails
		foreach ($emailList as $singleEmail){ 
			$singleEmail = trim($singleEmail);
			//nutzen des PHP E-Mail validierungsfilters
			if (filter_var($singleEmail, FILTER_VALIDATE_EMAIL)) { 
				$validEmails[] = $singleEmail;
			} else {
				$invalidEmails[] = $singleEmail;
			}	
		}
		//holen des Betreffs
		$subject = $this->request->subject; 
		try {
			//vorbereiten der E-Mail fürs versenden 	
			$email = $this->mailer->createMessage();  		
			$email->setFrom([$fromEmail]);
			$email->setSubject($subject);
			$email->setPlainBody($emailTemplate->renderText());
			$email->setHtmlBody($emailTemplate->renderHtml());
			
			//versenden der E-Mail an jeden validierten Empfänger
			foreach ($validEmails as $singleEmail){  
				$email->setTo([$singleEmail]);
				$this->mailer->send($email);
			}
		} 
		catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'FileDrop']);
		}
		//füllen eines return Arrays für die Abschlusseite
		$returnArray = ['validEmails' => $validEmails, 'invalidEmails' => $invalidEmails]; 	
		return $returnArray;
	}

	/**
	* Hohlt sich die ID des aktuellen Users
	*/
	private function getUser(){
		//bereitstellen der UserId durch Interface
		$user = $this->userManager->get($this->userId); 
		if (!$user instanceof IUser) {
			return array();
		}
		return $user;
	}

	/**
	* Hierher wurde das E-Mail Template ausgelagert
	*/
    private function buildEmailTemplate($link, $countedFiles, $fromEmail){
		//holender eingegebenen Nachricht
		$message = $this->request->message; 

		//für eine Datei
		if ($countedFiles === 1){ 
			$filledTemplate = $this->mailer->createEMailTemplate('FileDrop.Sendlink');
			$filledTemplate->addHeader();
			//durch '' wird der erste Parameter auch für Textmail genutzt
			$filledTemplate->addHeading($fromEmail . ' hat für Sie eine Datei über FileDrop bereitgestellt.',''); 
			$filledTemplate->addBodyText(htmlspecialchars('Es wurde folgende Nachricht für Sie hinterlassen:'), 'Für Sie wurde folgende Nachricht hinterlassen:');
			$filledTemplate->addBodyText(htmlspecialchars($message), $message);
			$filledTemplate->addBodyButton('Zu Ihrer Datei', $link,'');
			$filledTemplate->addFooter('Mit freundlichen Grüßen'. "<br/>" . 'Ihr FileDrop-Service' );
		}
		// für mehrere Dateien
		else{ 
			$filledTemplate = $this->mailer->createEMailTemplate('FileDrop.Sendlink');
			$filledTemplate->addHeader();
			$filledTemplate->addHeading($fromEmail . ' hat für Sie mehrere Dateien über FileDrop bereitgestellt.',''); 
			$filledTemplate->addBodyText(htmlspecialchars('Es wurde folgende Nachricht für Sie hinterlassen:'), 'Für Sie wurde folgende Nachricht hinterlassen:');
			$filledTemplate->addBodyText(htmlspecialchars($message), $message);
			$filledTemplate->addBodyButton('Zu Ihren Dateien', $link,'');
			$filledTemplate->addFooter('Mit freundlichen Grüßen'. "<br/>" . 'Ihr FileDrop-Service' );
		}
		return $filledTemplate;
	}	
}