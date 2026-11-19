# TYPO3 Extension `dsh_mastodon`

This extension enables automated publishing of records from your TYPO3 backend
to a [Mastodon](https://joinmastodon.org/) account.

## Installation

```
composer req diakonie-sh/dsh-mastodon
```

## Setup

The setup for automated publishing consists of three parts:

1. authorizing TYPO3 to the Mastodon account you want to use,
2. configuring which records will be published and how their fields translate to the status text,
3. setting up a scheduler task which periodically sends new posts to Mastodon.


## 1: Adding a Mastodon account

This view is only accessible to administrators.

Open the "Mastodon" module in the TYPO3 backend and choose "Admin" in the dropdown at the top.
The account will be saved to the root level, unless you navigate to a different folder while you are
in the Mastodon module.

1. First, enter the domain name of your Mastodon instance.
2. Then click on "create application" to navigate to the Development settings in your Mastodon settings. You may have to log in.
3. Choose a name (such as "TYPO3"), add the redirect URI as printed in TYPO3, then select the four needed permission scopes.
4. After creating the app in Mastodon, click on its name to access its details.
5. Copy the "Client Key" and "Client Secret" over to TYPO3.
6. Finish the authorization flow by submitting the form.

You will be redirected to Mastodon to allow access for TYPO3, then Mastodon will send you back to your TYPO3 system.
TYPO3 should show its login screen again, where you need to re-enter your administrator credentials.
This is necessary due to the CSRF protections in the TYPO# backend.

## 2: Configuring automatic creation of posts

The configuration of the DataHandler hook is done via Page TsConfig. You can do this in the "Resources" tab of your
site root, or in a folder, or even in the TsConfig of your site package.

Example configuration to create posts from EXT:news records:

```tsconfig
tx_dshmastodon {
  account = 1
  visibility = 0
  delay = +1 hour

  tx_news_domain_model_news {
    pid = 100
    autoCreate = 0
    autoUpdate = 1
    imageField = fal_media

    hashtags = First Second Third

    link {
      pageUid = 200
      parameter = 200
      additionalParams.dataWrap = &tx_news_pi1[controller]=News&tx_news_pi1[action]=detail&tx_news_pi1[news]={field:uid_foreign}
      forceAbsoluteUrl = 1
    }
  }
}
```

### `tx_dshmastodon.account`

The UID of the account which will be used in this page tree for automated posts.

Can be overridden per table.

### `tx_dshmastodon.visibility`

The post visibility:

* `0`: public
* `1`: unlisted
* `2`: private
* `4`: direct message

Can be overridden per table.

### `tx_dshmastodon.delay`

How much time to wait for a post to be published after the corresponding record is visible. Default is `+1 hour`.

This is calculated from the last change time, which is when the editor saves the record, or from the `starttime` publish date.

Can be overridden per table.

### `tx_dshmastodon.[table].pid`

Where to save the Mastodon posts in the page tree.

### `tx_dshmastodon.[table].autoCreate`

Whether to automatically create a post whenever a record of this table is updated (saved).

This can also be a date string `YYYY-MM-DD` to avoid publishing posts for older records. This date is checked against the creation date.

### `tx_dshmastodon.[table].autoUpdate`

Whether a change in the referenced record should trigger an update of the Mastodon post.

### `tx_dshmastodon.[table].imageField`

Which column to use to copy image file references for up to 4 images attached to the Mastodon post.

### `tx_dshmastodon.[table].hashtags`

Space-separated word list of hashtags.

### `tx_dshmastodon.[table].link.pageUid`

Is used to find out the site of the target link.

### `tx_dshmastodon.[table].link.parameter`

Typolink parameter for the detail view link of the record.

The other properties are the usual of a typolink content object (see `stdWrap.typolink` in the TYPO3 documentation).

### `tx_dshmastodon.[table].template`

Optional content object to use for creating the post text.

The default structure of the plain text post is:

```
{title}

{teaser}

{link}

{hashtags}
```

Where only title is mandatory, taken from the `label` column of the reference table.

`teaser` is taken from the `teaser` plain text field (TODO: make this configurable). Take care to not have too much
text in this field, as the default Mastodon post size limit is 500 characters, including the hashtags and 25 characters
for each link.

The link and hashtags are passed in to the content object alongside the record properties as `_link` and `_hashtags`.
They can be accessed via `dataWrap` for example, `{field:_link}` and `{field:_hashtags}`.


## 3: Set up a scheduler task to automate publishing

The "Publish Mastodon Posts" scheduler task can be used to send all posts to Mastodon, which are:

* not hidden,
* not manually unpublished,
* and with a publish time in the past.

This means that in order to prevent a post from being published after the configured delay, you should hide it.

If a post is accidentally sent, you can unpublish it in the Mastodon module, thereby blocking it from being published
automatically again.

Note: if you manually unpublished a post, you will have to wait up to 1 hour for Mastodon to forget it has already seen
this post, before you attempt to re-publish it.
Alternatively you can always delete the post and recreate it.


## Adding the mastodon post to other records

This is done via TCA, see `Configuration/TCA/Overrides/tx_news_domain_model_news.php` as the example.
