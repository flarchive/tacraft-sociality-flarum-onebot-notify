# OneBot Notify for Flarum

Send OneBot (QQ) notifications about new discussions and replies on your Flarum forum.

## Features

- Notifications for new discussions and replies
- Customizable message templates with placeholders
- Multiple group / private targets (comma-separated)
- Tag-based filtering (optional, requires flarum/tags)
- Optional access token and proxy support

## Installation

```bash
composer require tacraft-sociality/flarum-onebot-notify
php flarum cache:clear