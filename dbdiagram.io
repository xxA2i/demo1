// ============================================================
// Информационная система ООО 'СтройМатериалы'
// Диаграмма для dbdiagram.io
// ============================================================

// --- Таблицы-справочники (Словари) ---

Table roles {
  id int [pk, increment]
  name varchar(50) [not null, unique]
}

Table categories {
  id int [pk, increment]
  name varchar(150) [not null, unique]
}

Table suppliers {
  id int [pk, increment]
  name varchar(150) [not null, unique]
}

Table manufacturers {
  id int [pk, increment]
  name varchar(150) [not null, unique]
}

Table units {
  id int [pk, increment]
  name varchar(20) [not null, unique]
}

Table order_statuses {
  id int [pk, increment]
  name varchar(50) [not null, unique]
}

// --- Основные таблицы ---

Table pickup_points {
  id int [pk, increment]
  address text [not null]
}

Table users {
  id int [pk, increment]
  full_name varchar(150) [not null]
  login varchar(150) [not null, unique]
  password_hash varchar(255) [not null]
  role_id int [not null]
  // Индекс для внешнего ключа
  indexes {
    role_id
  }
}

Table products {
  id int [pk, increment]
  article varchar(20) [not null, unique]
  name varchar(200) [not null]
  category_id int [not null]
  description text
  manufacturer_id int [not null]
  supplier_id int [not null]
  unit_id int [not null]
  price decimal(10,2) [not null]
  stock int [not null, default: 0]
  discount int [not null, default: 0]
  photo varchar(100)
  // Индексы для внешних ключей
  indexes {
    category_id
    manufacturer_id
    supplier_id
    unit_id
  }
}

Table orders {
  id int [pk, increment]
  article_code varchar(30) [not null]
  order_date date
  delivery_date date
  pickup_point_id int
  client_id int
  receive_code varchar(20)
  status_id int
  // Индексы для внешних ключей
  indexes {
    pickup_point_id
    client_id
    status_id
  }
}

Table order_items {
  id int [pk, increment]
  order_id int [not null]
  product_id int [not null]
  quantity int [not null]
  // Индексы для внешних ключей
  indexes {
    order_id
    product_id
  }
}

// ============================================================
// Определение отношений (Связей)
// ============================================================

// Связи для таблицы users
Ref: users.role_id > roles.id

// Связи для таблицы products
Ref: products.category_id > categories.id
Ref: products.manufacturer_id > manufacturers.id
Ref: products.supplier_id > suppliers.id
Ref: products.unit_id > units.id

// Связи для таблицы orders
Ref: orders.pickup_point_id > pickup_points.id
Ref: orders.client_id > users.id
Ref: orders.status_id > order_statuses.id

// Связи для таблицы order_items
Ref: order_items.order_id > orders.id
Ref: order_items.product_id > products.id
