# yeswiki-extension-webhooks
Outgoing webhooks at each addition/modification/deletion of a Bazar item.

## Installation

Put this repository inside the `/tools/webhooks` directory of YesWiki.

## Configuration

Go to the BazaR main page, at the bottom you have a Webhooks section.

- On the left you have a dropdown menu to select the format: `Raw`, `Mattermost`, `Slack`
- On the right you must enter the URL of the webhook to call

Every time you click on Update, a new row will be added. If you wish to delete a webhook, just use a blank URL and it will be removed on update.

### Mattermost

To integrate the webhook with a Mattermost chatroom, follow this guideline:

- Log into your Mattermost chatroom as an admin
- On the main menu, click on "Integration" (you only see it if you're an admin)
- Choose "Incoming webhooks"
- Create a webhook with the configurations you wish to use
- On the YesWiki BazaR page, choose "Mattermost" and copy the URL provided by Mattermost

### Slack

To let Slack handle an incoming webhook, follow the [guide](https://api.slack.com/incoming-webhooks) here.

On the YesWiki BazaR page, choose "Slack" and copy the URL provided by Slack.

### Raw

If you choose the "Raw" format, all informations about the given Bazar item will be POSTed to the given URLs.

Additionnally, these information will be POSTed:

- `action`: action type (add/edit/delete)
- `text`: formatted text describing the action done

If you wish to see the data returned by the webhook, you can use the URL of the test incoming webhook: http://YOUR_URL/?BazaR&vue=test-webhook. All data POSTed to this URL will be inserted into the `yeswiki_triples` table.
