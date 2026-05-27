# Laravel Product Catalog API

This project is a REST API built with Laravel for managing and retrieving products and categories.

The implementation is designed around a product catalog where:

- A product can belong to many categories.
- A category can contain many products.
- Product prices are stored as integer cents.
- Product listing supports filtering, searching, and cursor pagination.
- Category responses include a stored `products_count` value for better performance under heavy traffic.

---

## Main Features

- `GET /api/products`
- `GET /api/products/{id}`
- `GET /api/categories`
- Many-to-many relationship between products and categories
- Pivot table: `category_product`
- Product filtering by price
- Product filtering by categories
- Product search by title and content (Laravel Scout + Meilisearch)
- Cursor pagination for product listing
- Cached category listing
- Seeded demo data:
  - 10 categories
  - 100 products
  - Random product-category relations

---

## Database Structure

### `products`

| Column | Description |
|---|---|
| `id` | Product ID |
| `price` | Product price stored in cents. Example: `4999` means `49.99` |
| `title` | Product title |
| `content` | Product description/content |
| `image` | Nullable product image path |
| `is_active` | Whether the product is visible through the API |
| `created_at` / `updated_at` | Laravel timestamps |

Important indexes:

```php
$table->index(['is_active', 'id']);
$table->index(['price', 'id']);
$table->index(['created_at', 'id']);
```

Full-text search is **not** stored in MySQL. Product text search runs through **Laravel Scout** and **Meilisearch**, which scales to very large catalogs without `FULLTEXT` table scans. Index settings live in `config/scout.php` (`price`, `is_active`, `category_ids` are filterable).

---

### `categories`

| Column | Description |
|---|---|
| `id` | Category ID |
| `title` | Category title |
| `products_count` | Stored number of products in the category |
| `created_at` / `updated_at` | Laravel timestamps |

`products_count` is denormalized. It is stored directly in the `categories` table so the API does not need to calculate product counts live on every request.

---

### `category_product`

Pivot table for the many-to-many relation between products and categories.

| Column | Description |
|---|---|
| `category_id` | Related category ID |
| `product_id` | Related product ID |

The composite primary key prevents duplicate product-category relations:

```php
$table->primary(['category_id', 'product_id']);
```

The reverse index improves product-to-categories lookup:

```php
$table->index(['product_id', 'category_id']);
```

---

## Installation

Clone the project and install dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database connection in `.env`.

Example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_catalog
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seed the database:

```bash
php artisan migrate:fresh --seed
```

### Search (Scout + Meilisearch)

With Docker:

```bash
docker compose up -d meilisearch
```

Set in `.env` (see `.env.example`):

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=dev-master-key
MEILISEARCH_MAX_TOTAL_HITS=10000
```

Apply Meilisearch index settings and import products (run inside the app container if you use Docker):

```bash
docker compose exec app php artisan scout:sync-index-settings
docker compose exec app php artisan scout:import "App\Models\Product"
```

`migrate:fresh --seed` also calls `Product::makeAllSearchable()` so the index is populated after seeding.

For large catalogs (e.g. millions of rows), run imports in chunks via Scout’s queue (`SCOUT_QUEUE=true`) and keep Meilisearch on a dedicated host with enough RAM for your index size.

Start the Laravel development server:

```bash
php artisan serve
```

The API will usually be available at:

```text
http://127.0.0.1:8000
```

### API rate limits

Per-IP limits (configure in `.env`):

| Variable | Default | Applies to |
|---|---:|---|
| `API_RATE_LIMIT_PER_MINUTE` | 60 | All `/api/*` routes |
| `API_PRODUCTS_RATE_LIMIT_PER_MINUTE` | 30 | `GET /api/products` |
| `API_SEARCH_RATE_LIMIT_PER_MINUTE` | 15 | `GET /api/products` when `q` is set |

Exceeded limits return HTTP `429 Too Many Requests`.


---

## Seeded Data

The database seeder creates:

- 10 example categories
- 100 example products
- Random relations between products and categories
- Updated `products_count` values for all categories

After seeding, the following endpoints can be tested immediately:

```text
http://127.0.0.1:8000/api/products
http://127.0.0.1:8000/api/categories
```

---

# API Endpoints

---

## 1. Get Products

```http
GET /api/products
```

Returns a cursor-paginated list of active products.

The product list response is intentionally lightweight. It returns:

- product ID
- formatted price
- title
- image
- categories only when requested with `include=categories`

---

## Query Parameters

| Parameter | Type | Required | Description |
|---|---:|---:|---|
| `q` | string | No | Search by product title and content |
| `price_min` | number | No | Minimum product price, using normal decimal format |
| `price_max` | number | No | Maximum product price, using normal decimal format |
| `category_ids[]` | array | No | Filter products by one or more categories |
| `per_page` | integer | No | Number of results per page. Max: `100` |
| `cursor` | string | No | Cursor pagination token |
| `include` | string | No | Use `include=categories` to include categories |

---

## Test in Browser

Open this URL in Firefox, Chrome, Safari, or another browser:

```text
http://127.0.0.1:8000/api/products
```

Expected result:

```json
{
  "data": [
    {
      "id": 1,
      "price": "49.99",
      "title": "Example product title",
      "image": "products/example.jpg"
    }
  ],
  "links": {
    "first": null,
    "last": null,
    "prev": null,
    "next": "http://127.0.0.1:8000/api/products?cursor=..."
  },
  "meta": {
    "per_page": 50
  }
}
```

---

## Test Products with Categories

```text
http://127.0.0.1:8000/api/products?include=categories
```

Expected result:

```json
{
  "data": [
    {
      "id": 1,
      "price": "49.99",
      "title": "Example product title",
      "image": "products/example.jpg",
      "categories": [
        {
          "id": 1,
          "title": "Electronics",
          "products_count": 20
        }
      ]
    }
  ]
}
```

---

## Test Price Filtering

The API accepts normal decimal prices:

```text
http://127.0.0.1:8000/api/products?price_min=10&price_max=100
```

Internally, these are converted to cents:

| API value | Database value |
|---:|---:|
| `10` | `1000` |
| `100` | `10000` |

So this request returns products with prices between `10.00` and `100.00`.

---

## Test Search

```text
http://127.0.0.1:8000/api/products?q=phone
```

Search uses **Meilisearch** (via Scout) on `title` and `content`. Price and category filters are applied in the search index, not with MySQL `FULLTEXT`.

Search also uses **cursor** pagination (`links.next`), backed by Meilisearch `offset`/`limit` (faster than deep `page` offsets). The cursor encodes a search `offset`, not a product `id`. Deep search is capped by `MEILISEARCH_MAX_TOTAL_HITS` (default `10000`) on the Meilisearch index.

Because the seeded products use fake text, some search terms may return an empty result. To test search reliably, first open `/api/products`, copy a word from an existing product title, and search for that word.

Example:

```text
http://127.0.0.1:8000/api/products?q=architecto
```

---

## Test Category Filtering

Filter by one category:

```text
http://127.0.0.1:8000/api/products?category_ids[]=1
```

Encoded version, useful if the browser changes the URL:

```text
http://127.0.0.1:8000/api/products?category_ids%5B%5D=1
```

Filter by multiple categories:

```text
http://127.0.0.1:8000/api/products?category_ids[]=1&category_ids[]=3
```

Encoded version:

```text
http://127.0.0.1:8000/api/products?category_ids%5B%5D=1&category_ids%5B%5D=3
```

This returns products that belong to category `1` or category `3`.

---

## Test Combined Filters

```text
http://127.0.0.1:8000/api/products?price_min=10&price_max=500&category_ids[]=1&include=categories&per_page=20
```

This tests:

- price filtering
- category filtering
- category inclusion
- custom page size

---

## Test Pagination

```text
http://127.0.0.1:8000/api/products?per_page=10
```

The response contains a `links.next` URL.

Open the `next` URL in the browser to test cursor pagination.

Example:

```text
http://127.0.0.1:8000/api/products?cursor=eyJpZCI6MTB9
```

Cursor pagination is used instead of offset pagination because the products table may contain millions of records.

---

## Validation Cases to Test

Invalid `per_page`:

```text
http://127.0.0.1:8000/api/products?per_page=500
```

Expected result: validation error because `per_page` must be at most `100`.

Invalid price range:

```text
http://127.0.0.1:8000/api/products?price_min=100&price_max=10
```

Expected result: validation error because `price_max` must be greater than or equal to `price_min`.

Invalid category ID:

```text
http://127.0.0.1:8000/api/products?category_ids[]=999999
```

Expected result: validation error if the category does not exist.

Too short search query:

```text
http://127.0.0.1:8000/api/products?q=a
```

Expected result: validation error because `q` must contain at least 2 characters.

---

# 2. Get Single Product

```http
GET /api/products/{id}
```

Example:

```text
http://127.0.0.1:8000/api/products/1
```

Expected response:

```json
{
  "data": {
    "id": 1,
    "price": "49.99",
    "title": "Example product title",
    "content": "Full product content...",
    "image": "products/example.jpg",
    "is_active": true,
    "categories": [
      {
        "id": 1,
        "title": "Electronics",
        "products_count": 20
      }
    ]
  }
}
```

If the product is inactive or does not exist, the API returns `404` (same response — inactive IDs are not resolved).

---

# 3. Get Categories

```http
GET /api/categories
```

Returns all categories with their stored product counts.

Test in browser:

```text
http://127.0.0.1:8000/api/categories
```

Expected response:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Electronics",
      "products_count": 20
    },
    {
      "id": 2,
      "title": "Books",
      "products_count": 14
    }
  ]
}
```

This endpoint uses the `products_count` column instead of calculating product counts live from the pivot table.

---

# What Should Be Tested

## Product API

Test that:

- Products are returned successfully.
- Only active products are returned.
- Product prices are formatted correctly.
- Product list does not return full `content`.
- Product detail returns full `content`.
- Cursor pagination works.
- `per_page` limit works.
- Search by `title` and `content` works.
- Price filtering works.
- Category filtering works.
- Multiple category filtering works.
- `include=categories` loads related categories.
- Invalid query parameters return validation errors.

---

## Category API

Test that:

- Categories are returned successfully.
- Each category contains `products_count`.
- Categories are ordered by title.


# Example Manual Test Checklist

Use these URLs after running `php artisan serve`:

```text
http://127.0.0.1:8000/api/categories
http://127.0.0.1:8000/api/products
http://127.0.0.1:8000/api/products?include=categories
http://127.0.0.1:8000/api/products?per_page=10
http://127.0.0.1:8000/api/products?price_min=10&price_max=100
http://127.0.0.1:8000/api/products?category_ids[]=1
http://127.0.0.1:8000/api/products?category_ids[]=1&category_ids[]=3
http://127.0.0.1:8000/api/products?price_min=10&price_max=500&category_ids[]=1&include=categories&per_page=20
http://127.0.0.1:8000/api/products/1
```


