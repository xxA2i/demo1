## Состав проекта

| Файл | Назначение |
|------|------------|
| `config.php` | Подключение к MySQL через `mysqli`, старт сессии, хелпер `e()`. |
| `index.php` | Страница авторизации (логин/пароль) + вход как гость. |
| `dashboard.php` | Единая панель управления. Интерфейс разделён по ролям через `if ($_SESSION["role"] === ...)`. |
| `product_form.php` | Добавление/редактирование товара (администратор), загрузка фото. |
| `product_delete.php` | Удаление товара (администратор). Запрет удаления товара в заказе. |
| `order_form.php` | Добавление/редактирование заказа (администратор). |
| `order_delete.php` | Удаление заказа (администратор). |
| `logout.php` | Выход из учётной записи на экран входа. |
| `manual.php` | Руководство пользователя. |
| `style.css` | Оформление (палитра по руководству по стилю). |
| `script.sql` | Полный SQL-скрипт: схема БД в 3НФ + все данные импорта. |
| `adduser.sql` | Создание пользователя MySQL `student` с паролем `password`. |
| `assets/` | Иконка, логотип, заглушка `picture.png`, папка `photos/` с фото товаров. |


```bash
apt install apache2 mysql-server php php-mysql php-gd libapache2-mod-php nano -y
git clone https://github.com/xxA2i/demo1.git
cp -r /home/sorrry/demo1/* /var/www/html/
rm /home/sorrry/demo1 -r -f
rm manual.php 

sudo systemctl enable --now apache2
sudo systemctl enable --now mysql
```

# Применение основного скрипта (создаёт БД stroymaterialy и наполняет данными)
sudo mysql < /var/www/html/script.sql
sudo mysql < /var/www/html/adduser.sql

```bash
mysql -u student -ppassword -e "USE stroymaterialy; SHOW TABLES;"
```


```bash
sudo chown -R www-data:www-data /var/www/html/assets/photos
sudo chmod -R 755 /var/www/html/assets
```

| Роль | Логин | Пароль |
|------|-------|--------|
| Администратор	| Ворсин Петр Евгеньевич	| 94d5ous@gmail.com	| uzWC67
| Администратор	| Старикова Елена Павловна	| uth4iz@mail.com	| 2L6KZG
| Администратор	| Одинцов Серафим Артёмович	| yzls62@outlook.com	| JlFRCZ
| Менеджер	| Степанов Михаил Артёмович	| 1diph5e@tutanota.com	| 8ntwUp
| Менеджер	| Ворсин Петр Евгеньевич	| tjde7c@yahoo.com	| YOyhfR
| Менеджер	| Старикова Елена Павловна	| wpmrc3do@tutanota.com	| RSbvHv
| Авторизированный клиент	| Михайлюк Анна Вячеславовна	| 5d4zbu@tutanota.com	| rwVDh9
| Авторизированный клиент	| Ситдикова Елена Анатольевна	| ptec8ym@yahoo.com	| LdNyos
| Авторизированный клиент	| Никифорова Весения Николаевна	| 1qz4kw@mail.com	| gynQMT
| Авторизированный клиент	| Сазонов Руслан Германович	| 4np6se@mail.com	| AtnDjr


Полный список из 10 пользователей — в таблице `users`.
