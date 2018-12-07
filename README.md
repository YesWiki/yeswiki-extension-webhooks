# yeswiki-extension-webhooks
Automatic webhooks at each addition/modification/deletion of a Bazar item.

## Installation

Put this repository inside the `/tools/webhooks` directory of YesWiki.

## Configuration

Go to the BazaR main page, at the bottom you have a Webhooks section.

- On the left you have a dropdown menu to select the format: `Raw` or `Mattermost`
- On the right you must enter the URL of the webhook to call

Every time you click on Update, a new row will be added. If you wish to delete a webhook, just use a blank URL and it will be removed on update.

### Mattermost

To integrate the webhook with a Mattermost chatroom, follow this guideline:

- Log into your Mattermost chatroom as an admin
- On the main menu, click on "Integration" (you only see it if you're an admin)
- Choose "Incoming webhooks"
- Create a webhook with the configurations you wish to use
- On the YesWiki BazaR page, choose "Mattermost" and copy the URL provided by Mattermost

### Raw

If you choose the "Raw" format, all informations about the given Bazar item will be POSTed to the given URLs.
