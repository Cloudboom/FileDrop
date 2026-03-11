<template>
	<NcAppContent>
		<section class="filedrop">
			<header class="filedrop__header">
				<p class="filedrop__eyebrow">{{ t('filedrop', 'File Drop') }}</p>
				<h1>{{ t('filedrop', 'Modernized app baseline') }}</h1>
				<p class="filedrop__lead">
					{{ t('filedrop', 'This repository is now aligned with the current Nextcloud app stack and ready for the original upload flow to be rebuilt on top of it.') }}
				</p>
			</header>

			<div class="filedrop__grid">
				<article class="filedrop__card">
					<h2>{{ t('filedrop', 'Current status') }}</h2>
					<ul>
						<li>{{ t('filedrop', 'Vue 3 frontend with current @nextcloud/vue imports') }}</li>
						<li>{{ t('filedrop', 'Attribute-based routes and OCS API response') }}</li>
						<li>{{ t('filedrop', 'Compatibility metadata updated for Nextcloud 31 to 33') }}</li>
					</ul>
				</article>

				<article class="filedrop__card">
					<h2>{{ t('filedrop', 'What still needs product work') }}</h2>
					<ul>
						<li>{{ t('filedrop', 'The legacy file-upload and mail-share backend is not present in this repository') }}</li>
						<li>{{ t('filedrop', 'Recipient handling, storage, and sharing rules need a new implementation') }}</li>
						<li>{{ t('filedrop', 'The UI can now be extended without carrying forward the broken 2018 template code') }}</li>
					</ul>
				</article>
			</div>
		</section>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/components/NcAppContent'

export default {
	name: 'App',
	components: {
		NcAppContent,
	},
}
</script>

<style scoped lang="scss">
.filedrop {
	display: grid;
	gap: 24px;
	padding: 24px;
	max-width: 960px;
	margin: 0 auto;
}

.filedrop__header {
	display: grid;
	gap: 12px;
}

.filedrop__eyebrow {
	margin: 0;
	font-size: 0.875rem;
	font-weight: 700;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: var(--color-primary-element);
}

h1,
h2,
p,
ul {
	margin: 0;
}

.filedrop__lead {
	max-width: 60ch;
	color: var(--color-text-maxcontrast);
}

.filedrop__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 16px;
}

.filedrop__card {
	display: grid;
	gap: 12px;
	padding: 20px;
	border: 1px solid var(--color-border);
	border-radius: 16px;
	background: linear-gradient(180deg, var(--color-main-background) 0%, var(--color-background-hover) 100%);
	box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

ul {
	padding-left: 20px;
	color: var(--color-text-maxcontrast);
}

li + li {
	margin-top: 8px;
}

@media (max-width: 640px) {
	.filedrop {
		padding: 16px;
	}
}
</style>
