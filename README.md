Email To Web Setup
- composer require webklex/laravel-imap
- telnet imap.gmail.com 995 testing is connected or not 
- php artisan migrate --path=database/migrations/2024_07_10_103437_create_gmail_to_web_login_table.php one file 
  migration
- connection to webmail to check a imap is enable after that login use username -> mailId and password -> 
  password

Gmail To Web Setup
- connection to gmail to check imap is enable and also two step verification -> app password set in your gmail 
  settings after that login use username -> mailId and password -> app password set