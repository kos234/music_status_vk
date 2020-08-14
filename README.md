# Music status for vk / Музыкальный статус для вк

![preview](preview.gif)
### Created using the libraries' / Создано с использованием библиотек: [php-getting-started](https://github.com/heroku/php-getting-started), [vk-php-sdk](https://github.com/VKCOM/vk-php-sdk)
## Connection / Подключение
### Step one: getting a spotify token / Шаг первый: получение токена Spotify

Click the [link](https://accounts.spotify.com/authorize?client_id=dde6a297cdc345059eda98c69ba722c0&response_type=code&redirect_uri=https://music-status-by-kos.herokuapp.com/spotify&scope=user-read-currently-playing) and allow the app to access your Spotify account, after which you will be redirected to the site with your token Spotify and refresh token

Перейдите по [ссылке](https://accounts.spotify.com/authorize?client_id=dde6a297cdc345059eda98c69ba722c0&response_type=code&redirect_uri=https://music-status-by-kos.herokuapp.com/spotify&scope=user-read-currently-playing) и разрешите доступ приложению к вашему аккаунту Spotify, после чего вас перенаправит на сайт с вашим токеном Spotify и токеном для смены

### Step two: getting a vk token / Шаг второй: получение токена VK

Click on the [link](https://oauth.vk.com/authorize?client_id=7445793&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=status,offline,photos&response_type=code&v=5.103) and allow access to your Vk account, then copy the link content, thank you stupid VK authorization

Перейдите по [ссылке](https://oauth.vk.com/authorize?client_id=7445793&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=status,offline,photos&response_type=code&v=5.103) и разрешите доступ к вашему аккаунту Vk, после чего скопируйте содержимое ссылки, спасибо тупой авторизации Vk

### Step three: connecting / Шаг третий: подключение

Click on the [link](https://vk.com/im?sel=-194913413) and write the bot the command `/start {Spotify token} {Spotify refresh token} {link received at step two}`

Перейдите по [ссылке](https://vk.com/im?sel=-194913413) и напишите боту команду `/начать {токен Spotify} {токен смены Spotify} {ссылка полученная на втором шаге}`

##### To find out the functions status, write `/info` or `/` to the bot / Чтобы узнать возможно статуса напишите `/инфо(а)` или `/` боту
