# File Drop

Modernized baseline for a Nextcloud app that originally aimed to upload files and share them by email.

## Current state

- Supports Nextcloud 31 to 33
- Uses a Vue 3 frontend with current `@nextcloud/vue` import paths
- Uses attribute-based routing and a small OCS health endpoint
- Removes the broken legacy HTML form that referenced controllers no longer present in the repository

## Remaining work

The original upload, storage, and mail-sharing workflow is not present in this repository anymore. Rebuilding that feature now has to happen on top of the cleaned-up baseline in this branch.

## Development

- `composer install`
- `npm install`
- `npm run build`

## Resources

- [Nextcloud developer manual](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Nextcloud app store publishing guide](https://nextcloudappstore.readthedocs.io/en/latest/developer.html)
