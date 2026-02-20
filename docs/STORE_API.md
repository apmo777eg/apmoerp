# ERP Store API (Store Integration)

This document describes **how external stores / connectors** (Shopify, WooCommerce, custom Laravel store, etc.) should connect to the **ERP Store API**.

> The API is **branch-scoped via the Store Token**. When a request is authenticated, the ERP automatically sets the request branch context to the store's branch.

---

## 1) Generate a Store API Token

1. Go to: **Admin → Stores**
2. Open the store you want to connect.

> Important: Each store must be assigned to a **specific branch** (required). The Store API token automatically sets the branch context.

3. In the **ERP Store API** section, click **Generate API Token**.
4. Copy the token immediately.

Notes:
- The token is **shown only once** after creation.
- You can **revoke** tokens at any time from the same screen.
- You can optionally set an **expiry date**.

---

## 2) Authentication

Send the token using the `Authorization` header:

- `Authorization: Bearer <STORE_API_TOKEN>`
- `Accept: application/json`

Deprecated authentication (query/body token) is disabled by default.

---

## 3) Abilities

Tokens can be restricted by ability.

| Area | Read ability | Write ability |
|------|--------------|---------------|
| Products | `products.read` | `products.write` |
| Inventory | `inventory.read` | `inventory.write` |
| Orders | `orders.read` | `orders.write` |
| Customers | `customers.read` | `customers.write` |

If the token has `*`, it is treated as **full access**.

---

## 4) Base URL

All endpoints are under:

- `/api/v1/...`

See the in-app documentation:

- **Admin → API Docs**

---

## 5) Quick cURL examples

### List products

```bash
curl -X GET "https://YOUR_ERP_DOMAIN/api/v1/products?per_page=50" \
  -H "Authorization: Bearer YOUR_STORE_API_TOKEN" \
  -H "Accept: application/json"
```

### Update stock (single)

```bash
curl -X POST "https://YOUR_ERP_DOMAIN/api/v1/inventory/update-stock" \
  -H "Authorization: Bearer YOUR_STORE_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "STORE-PROD-123",
    "qty": 5,
    "direction": "in",
    "reason": "Sync from store"
  }'
```

### Create an order

```bash
curl -X POST "https://YOUR_ERP_DOMAIN/api/v1/orders" \
  -H "Authorization: Bearer YOUR_STORE_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "STORE-ORDER-1001",
    "customer": {"name": "John Doe", "email": "john@example.com"},
    "items": [
      {"external_id": "STORE-PROD-123", "quantity": 2, "price": 100}
    ]
  }'
```

---

## 6) Webhooks

Webhook endpoints are:

- `POST /api/v1/webhooks/shopify/{storeId}`
- `POST /api/v1/webhooks/woocommerce/{storeId}`
- `POST /api/v1/webhooks/laravel/{storeId}`

Each store integration has a secret used for signature verification.

See **Admin → API Docs → Webhooks** for required headers and supported topics.
