# Telegram Wep App "Bool Questions" for contest

![Bool Questions](https://github.com/makhnanov/telegram-web-app-contest-bool-questions/blob/main/demo_bool_questions.gif?raw=true)

## Demo: [@BoolQuestions](https://t.me/BoolQuestions)

## Stack: Vanilla JavaScript + PHP without any framework + Redis for fastest storage

## This app allows you:
- Ask any simple (Yes / No) question to many other people.
- Answer to users questions
- Watch statistic of your polls.
- Watch history of your answers.

### Beginner developer perspective:
- Too less code. Just 260 lines on PHP and 204 lines on JS. No frameworks!

### Advanced developer perspective:
- This is example of long time ago dream, for create social app in tg, released in 24 hours. Need just start write code =)

### Users perspectives:
- This is very useful tool for make social pools, or thinking about unusual questions with a friends.
- Also, sometimes you can roll back and view what you choose. Probably you change. 
- Now in is not released, but if you can any coins or prizes, for your activity, it will be more funny.
- Also in plans add ability to skip / report bad questions, or add @username, what give ability to dating.

### Troubles
At the beginning I get strange (null) error and can't debug. But in one thread in github one ofe user say that in is probably SSL error. \
I watch 3 my *.crt certificates and combine this chain in one *.crt via next command, and this is solve my problem.
```shell
openssl pkcs7 -print_certs < list.p7b > chain.crt
```
Also, I too long setup nginx, so part of my config here:
```shell
server {
    listen 80 default_server;
    server_name  example.com;
    return 302 https://$server_name$request_uri;
}

server {
    server_name www.bro-launcher.com;
    return 301 $scheme://example.com$request_uri;
}

server {
        listen 443 ssl default_server;
        listen [::]:443 ssl default_server;

        ssl_certificate /root/crt.crt;
        ssl_certificate_key /root/key.key;

        error_log /var/log/nginx/err.txt;

        index index.html index.php;

        location /api {
            root /var/www/telegram-contest-bool-questions/;
            try_files $uri /index.php$is_args$args;
        }

        location / {
            index index.html;
            root /var/www/other-site;
        }

        location ~ \.php$ {
            fastcgi_index index.php;
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/telegram-contest-bool-questions$fastcgi_script_name;
            include fastcgi_params;
      }
      
      location /telegram-contest-bool-questions/ {
          alias /var/www/telegram-contest-bool-questions/;
          try_files $uri $uri/ =404;
      }
}
```

### Gratitude
- Especially thanks https://github.com/OxMohsen for his https://github.com/OxMohsen/validating-data library for validate telegram hash for user data.

#### Installation
```shell
composer install
```
