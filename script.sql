-- ============================================================
-- Информационная система ООО 'СтройМатериалы'
-- СУБД: MySQL 8 / MariaDB. Кодировка utf8mb4.
-- Схема в 3-й нормальной форме (3NF), ссылочная целостность ON.
-- ============================================================
DROP DATABASE IF EXISTS stroymaterialy;
CREATE DATABASE stroymaterialy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stroymaterialy;

SET FOREIGN_KEY_CHECKS = 0;

-- Роли пользователей (справочник)
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO roles (id, name) VALUES
    (1,'Администратор'), (2,'Менеджер'), (3,'Авторизированный клиент');

-- Категории товаров
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO categories (name) VALUES
    ('Общестроительные материалы'),
    ('Стеновые и фасадные материалы'),
    ('Сухие строительные смеси и гидроизоляция'),
    ('Ручной инструмент'),
    ('Защита лица, глаз, головы');

-- Поставщики
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO suppliers (name) VALUES
    ('М500'),
    ('Изостронг'),
    ('Knauf'),
    ('MixMaster'),
    ('ЛСР'),
    ('ВОЛМА'),
    ('Vinylon'),
    ('Павловский завод'),
    ('Weber'),
    ('Hesler'),
    ('Armero'),
    ('Wenzo Roma'),
    ('KILIMGRIN'),
    ('Исток'),
    ('RUIZ'),
    ('Husqvarna'),
    ('Delta');

-- Производители
CREATE TABLE manufacturers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO manufacturers (name) VALUES
    ('М500'),
    ('Изостронг'),
    ('Knauf'),
    ('MixMaster'),
    ('ЛСР'),
    ('ВОЛМА'),
    ('Vinylon'),
    ('Павловский завод'),
    ('Weber'),
    ('Hesler'),
    ('Armero'),
    ('Wenzo Roma'),
    ('KILIMGRIN'),
    ('Исток'),
    ('RUIZ'),
    ('Husqvarna'),
    ('Delta');

-- Единицы измерения
CREATE TABLE units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO units (name) VALUES
    ('шт.');

-- Статусы заказа
CREATE TABLE order_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO order_statuses (name) VALUES
    ('Завершен'),
    ('Новый');

-- Пункты выдачи
CREATE TABLE pickup_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    address TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO pickup_points (address) VALUES
    ('125061, г. Лесной, ул. Подгорная, 8'),
    ('630370, г. Лесной, ул. Шоссейная, 24'),
    ('400562, г. Лесной, ул. Зеленая, 32'),
    ('614510, г. Лесной, ул. Маяковского, 47'),
    ('410542, г. Лесной, ул. Светлая, 46'),
    ('620839, г. Лесной, ул. Цветочная, 8'),
    ('443890, г. Лесной, ул. Коммунистическая, 1'),
    ('603379, г. Лесной, ул. Спортивная, 46'),
    ('603721, г. Лесной, ул. Гоголя, 41'),
    ('410172, г. Лесной, ул. Северная, 13'),
    ('614611, г. Лесной, ул. Молодежная, 50'),
    ('454311, г.Лесной, ул. Новая, 19'),
    ('660007, г.Лесной, ул. Октябрьская, 19'),
    ('603036, г. Лесной, ул. Садовая, 4'),
    ('394060, г.Лесной, ул. Фрунзе, 43'),
    ('410661, г. Лесной, ул. Школьная, 50'),
    ('625590, г. Лесной, ул. Коммунистическая, 20'),
    ('625683, г. Лесной, ул. 8 Марта'),
    ('450983, г.Лесной, ул. Комсомольская, 26'),
    ('394782, г. Лесной, ул. Чехова, 3'),
    ('603002, г. Лесной, ул. Дзержинского, 28'),
    ('450558, г. Лесной, ул. Набережная, 30'),
    ('344288, г. Лесной, ул. Чехова, 1'),
    ('614164, г.Лесной,  ул. Степная, 30'),
    ('394242, г. Лесной, ул. Коммунистическая, 43'),
    ('660540, г. Лесной, ул. Солнечная, 25'),
    ('125837, г. Лесной, ул. Шоссейная, 40'),
    ('125703, г. Лесной, ул. Партизанская, 49'),
    ('625283, г. Лесной, ул. Победы, 46'),
    ('614753, г. Лесной, ул. Полевая, 35'),
    ('426030, г. Лесной, ул. Маяковского, 44'),
    ('450375, г. Лесной ул. Клубная, 44'),
    ('625560, г. Лесной, ул. Некрасова, 12'),
    ('630201, г. Лесной, ул. Комсомольская, 17'),
    ('190949, г. Лесной, ул. Мичурина, 26');

-- Пользователи системы (сотрудники и клиенты)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    login VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Пароль хранится в виде SHA-256 (password_hash).
-- Примечание: в задании указано 'логин и пароль, взятый из базы данных',
-- поэтому в столбце password_hash лежит SHA-256 от исходного пароля.
INSERT INTO users (full_name, login, password_hash, role_id) VALUES
    ('Ворсин Петр Евгеньевич', '94d5ous@gmail.com', 'd2820f125d23c3d0fafe06916a704986f3089e5fa0479b4c25161a272f00438a', 1),
    ('Старикова Елена Павловна', 'uth4iz@mail.com', '38bdbfe589c9f06ae66e0239e387560ec6fd0f1e461bbd920d27acf00673177b', 1),
    ('Одинцов Серафим Артёмович', 'yzls62@outlook.com', 'fd08d92fd54f393f46a1fc4ffbfcf83ba802e83683c4d7eafe8b5178c053915e', 1),
    ('Степанов Михаил Артёмович', '1diph5e@tutanota.com', '47893730c731bbab53f3e8c59d168ccc3267ddcc79aff8381f587db373c5ab4d', 2),
    ('Ворсин Петр Евгеньевич', 'tjde7c@yahoo.com', '8ed59a1b6200df2d0961eebadedcd75ff2f2cf193a41e5bcfa5992f4d2d734c1', 2),
    ('Старикова Елена Павловна', 'wpmrc3do@tutanota.com', '78eb1d08c2fdb9cb0a859a37e0e8604953f47a8819dc829d0efcc6f0699d0af7', 2),
    ('Михайлюк Анна Вячеславовна', '5d4zbu@tutanota.com', 'af40c4a08e1773a45a62c360eddb28141191c5ece01f7c596a76a12f119b18a5', 3),
    ('Ситдикова Елена Анатольевна', 'ptec8ym@yahoo.com', '717ee3bd45179a9ce91b03f015b98ba49a209ea704f0fbd8454f3c5e1b6c372d', 3),
    ('Никифорова Весения Николаевна', '1qz4kw@mail.com', 'eb7e715f922a40093afa46d1e1522b1ae83bf7bc4a1c1c2775c4f3cd1f8505dd', 3),
    ('Сазонов Руслан Германович', '4np6se@mail.com', '28fd0b15dd21c70fb93c7a8869bde5b3ea30b05d9000c8233f961666925697c2', 3);

-- Товары
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    manufacturer_id INT NOT NULL,
    supplier_id INT NOT NULL,
    unit_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    discount INT NOT NULL DEFAULT 0 CHECK (discount BETWEEN 0 AND 100),
    photo VARCHAR(100) DEFAULT NULL,
    CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_prod_man FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id),
    CONSTRAINT fk_prod_sup FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_prod_unit FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO products
    (article, name, category_id, description, manufacturer_id, supplier_id, unit_id, price, stock, discount, photo) VALUES
    ('PMEZMH', 'Цемент', 1, 'Цемент Евроцемент М500 Д0 ЦЕМ I 42,5 50 кг', 1, 1, 1, '440', 34, 8, 'PMEZMH.jpg'),
    ('BPV4MM', 'Пленка техническая', 1, 'Пленка техническая полиэтиленовая Изостронг 60 мк 3 м рукав 1,5 м, пог.м', 2, 2, 1, '8', 2, 8, 'BPV4MM.jpg'),
    ('JVL42J', 'Пленка техническая', 1, 'Пленка техническая полиэтиленовая Изостронг 100 мк 3 м рукав 1,5 м, пог.м', 2, 2, 1, '13', 34, 4, 'JVL42J.jpg'),
    ('F895RB', 'Песок строительный', 1, 'Песок строительный 50 кг', 3, 3, 1, '102', 7, 6, 'F895RB.jpg'),
    ('3XBOTN', 'Керамзит фракция', 1, 'Керамзит фракция 10-20 мм 0,05 куб.м', 4, 4, 1, '110', 21, 5, '3XBOTN.jpg'),
    ('3L7RCZ', 'Газобетон', 2, 'Газобетон ЛСР 100х250х625 мм D400', 5, 5, 1, '7400', 20, 2, '3L7RCZ.jpg'),
    ('S72AM3', 'Пазогребневая плита', 2, 'Пазогребневая плита ВОЛМА Гидро 667х500х80 мм полнотелая', 6, 6, 1, '500', 35, 5, 'S72AM3.jpg'),
    ('2G3280', 'Угол наружный', 2, 'Угол наружный Vinylon 3050 мм серо-голубой', 7, 7, 1, '795', 20, 9, '2G3280.jpg'),
    ('MIO8YV', 'Кирпич', 2, 'Кирпич рядовой Боровичи полнотелый М150 250х120х65 мм 1NF', 6, 6, 1, '30', 31, 9, 'MIO8YV.jpg'),
    ('UER2QD', 'Скоба для пазогребневой плиты', 2, 'Скоба для пазогребневой плиты Knauf С1 120х100 мм', 3, 3, 1, '25', 27, 8, 'UER2QD.jpg'),
    ('ZR70B4', 'Кирпич', 2, 'Кирпич рядовой силикатный Павловский завод полнотелый М200 250х120х65 мм 1NF', 8, 8, 1, '16', 0, 3, NULL),
    ('LPDDM4', 'Штукатурка гипсовая', 3, 'Штукатурка гипсовая Knauf Ротбанд 30 кг', 3, 3, 1, '500', 38, 6, NULL),
    ('LQ48MW', 'Штукатурка гипсовая', 3, 'Штукатурка гипсовая Knauf МП-75 машинная 30 кг', 9, 9, 1, '462', 33, 6, NULL),
    ('O43COU', 'Шпаклевка', 3, 'Шпаклевка полимерная Weber.vetonit LR + для сухих помещений белая 20 кг', 6, 6, 1, '750', 16, 1, NULL),
    ('M26EXW', 'Клей для плитки, керамогранита и камня', 3, 'Клей для плитки, керамогранита и камня Крепс Усиленный серый (класс С1) 25 кг', 3, 3, 1, '340', 0, 8, NULL),
    ('K0YACK', 'Смесь цементно-песчаная', 3, 'Смесь цементно-песчаная (ЦПС) 300 по ТУ MixMaster Универсал 25 кг', 4, 4, 1, '160', 19, 8, NULL),
    ('ASPXSG', 'Ровнитель', 3, 'Ровнитель (наливной пол) финишный Weber.vetonit 4100 самовыравнивающийся высокопрочный 20 кг', 9, 9, 1, '711', 20, 10, NULL),
    ('ZKQ5FF', 'Лезвие для ножа', 4, 'Лезвие для ножа Hesler 18 мм прямое (10 шт.)', 10, 10, 1, '65', 6, 6, NULL),
    ('4WZEOT', 'Лезвие для ножа', 4, 'Лезвие для ножа Armero 18 мм прямое (10 шт.)', 11, 11, 1, '110', 17, 6, NULL),
    ('4JR1HN', 'Шпатель', 4, 'Шпатель малярный 100 мм с пластиковой ручкой', 10, 10, 1, '26', 7, 6, NULL),
    ('Z3XFSP', 'Нож строительный', 4, 'Нож строительный Hesler 18 мм с ломающимся лезвием пластиковый корпус', 10, 10, 1, '63', 5, 8, NULL),
    ('I6MH89', 'Валик', 4, 'Валик Wenzo Roma полиакрил 250 мм ворс 18 мм для красок грунтов и антисептиков на водной основе с рукояткой', 12, 12, 1, '326', 3, 12, NULL),
    ('83M5ME', 'Кисть', 4, 'Кисть плоская смешанная щетина 100х12 мм для красок и антисептиков на водной основе', 11, 11, 1, '122', 26, 9, NULL),
    ('61PGH3', 'Очки защитные', 5, 'Очки защитные Delta Plus KILIMANDJARO (KILIMGRIN) открытые с прозрачными линзами', 13, 13, 1, '184', 25, 6, NULL),
    ('GN6ICZ', 'Каска защитная', 5, 'Каска защитная Исток (КАС001О) оранжевая', 14, 14, 1, '154', 8, 15, NULL),
    ('Z3LO0U', 'Очки защитные', 5, 'Очки защитные Delta Plus RUIZ (RUIZ1VI) закрытые с прозрачными линзами', 15, 15, 1, '228', 11, 9, NULL),
    ('QHNOKR', 'Маска защитная', 5, 'Маска защитная Исток (ЩИТ001) ударопрочная и термостойкая', 14, 14, 1, '251', 22, 2, NULL),
    ('EQ6RKO', 'Подшлемник', 5, 'Подшлемник для каски одноразовый', 16, 16, 1, '36', 22, 17, NULL),
    ('81F1WG', 'Каска защитная', 5, 'Каска защитная Delta Plus BASEBALL DIAMOND V UP (DIAM5UPBCFLBS) белая', 17, 17, 1, '1500', 13, 2, NULL),
    ('0YGHZ7', 'Очки защитные', 5, 'Очки защитные Husqvarna Clear (5449638-01) открытые с прозрачными линзами', 16, 16, 1, '700', 36, 9, NULL);

-- Заказы
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_code VARCHAR(30) NOT NULL,
    order_date DATE,
    delivery_date DATE,
    pickup_point_id INT,
    client_id INT,
    receive_code VARCHAR(20),
    status_id INT,
    CONSTRAINT fk_ord_pp FOREIGN KEY (pickup_point_id) REFERENCES pickup_points(id),
    CONSTRAINT fk_ord_client FOREIGN KEY (client_id) REFERENCES users(id),
    CONSTRAINT fk_ord_status FOREIGN KEY (status_id) REFERENCES order_statuses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 'Артикул заказа' из импорта (например PMEZMH,2,BPV4MM,2)
-- нормализован в таблицу order_items (связка заказ-товар, 3НФ).
INSERT INTO orders
    (id, article_code, order_date, delivery_date, pickup_point_id, client_id, receive_code, status_id) VALUES
    (1, 'PMEZMH, 2, BPV4MM, 2', '2025-02-27', '2025-04-20', 1, 7, '901', 1),
    (2, 'JVL42J, 1, F895RB, 1', '2024-09-28', '2025-04-21', 11, 8, '902', 1),
    (3, '3XBOTN, 10, 3L7RCZ, 10', '2025-03-21', '2025-04-22', 2, 9, '903', 1),
    (4, 'S72AM3, 5, 2G3280, 4', '2025-02-20', '2025-04-23', 11, 10, '904', 1),
    (5, 'MIO8YV, 2, UER2QD, 2', '2025-03-17', '2025-04-24', 2, 7, '905', 1),
    (6, 'ZR70B4, 1, LPDDM4, 1', '2025-03-01', '2025-04-25', 15, 8, '906', 1),
    (7, 'LQ48MW, 10, O43COU8, 10', '2025-02-28', '2025-04-26', 3, 9, '907', 1),
    (8, 'M26EXW, 5, K0YACK, 4', '2025-03-31', '2025-04-27', 19, 10, '908', 2),
    (9, 'ASPXSG, 5, ZKQ5FF, 1', '2025-04-02', '2025-04-28', 5, 9, '909', 2),
    (10, '4WZEOT, 5, 4JR1HN, 5', '2025-04-03', '2025-04-29', 19, 10, '910', 2);

-- Состав заказов (связка заказ-товар с количеством) - 3НФ
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO order_items (order_id, product_id, quantity) VALUES
    (1, 1, 2),
    (1, 2, 2),
    (2, 3, 1),
    (2, 4, 1),
    (3, 5, 10),
    (3, 6, 10),
    (4, 7, 5),
    (4, 8, 4),
    (5, 9, 2),
    (5, 10, 2),
    (6, 11, 1),
    (6, 12, 1),
    (7, 13, 10),
    (7, 14, 10),
    (8, 15, 5),
    (8, 16, 4),
    (9, 17, 5),
    (9, 18, 1),
    (10, 19, 5),
    (10, 20, 5);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Конец скрипта: схема базы данных и данные импорта готовы.
-- ============================================================