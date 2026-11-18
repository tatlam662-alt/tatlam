# PanDaShop Vietnam Ecommerce extension

## Package structure

```text
upload/extension/vietnam_ecommerce/
├── install.json
├── README.md
├── admin/
│   ├── controller/{module/settings.php,payment/vietqr.php,shipping/ghtk.php}
│   ├── language/{en-gb,vi-vn}/...
│   └── view/template/{module/settings.twig,payment/vietqr.twig,shipping/ghtk.twig}
└── catalog/
    ├── controller/{address/lookup.php,checkout/address.php,event/checkout.php,event/order.php,payment/vietqr.php}
    ├── language/{en-gb,vi-vn}/...
    ├── model/{event/telegram.php,payment/vietqr.php,shipping/ghtk.php}
    └── view/template/{checkout/address_fields.twig,checkout/single_page.twig,payment/vietqr.twig}
```

## Setup status

The database tables and OpenCart event registrations have already been installed for this project. Temporary installer, diagnostic, and SQL migration files have been removed from this web-accessible extension directory.

Configure credentials and enable methods from the Admin settings page or their individual Shipping and Payment extension pages. Secret fields are write-only; leaving a secret blank retains its saved value. For GHTK, set the pickup province/district and product weights in grams. District/ward shipping requires a complete, current Vietnam address dataset.

## GHTK

The fee model calls `GET /services/shipment/fee` with a `Token` header and destination/pickup details. If fallback is enabled, it returns the configured estimated fee when the token is missing, the address is incomplete, or the API call fails. Live rates require valid GHTK credentials and address names that match GHTK records.

## VietQR and webhook

The QR image is generated for the current order amount after the order is created. The webhook route is `index.php?route=extension/vietnam_ecommerce/payment/vietqr.webhook`; it accepts POST JSON with `order_id` and `amount`, and requires the configured `X-VietQR-Token`. It verifies the VND order total and duplicate paid state before adding the paid status. QR generation alone does not verify bank transfers; a trusted payment provider must call the webhook. Use HTTPS and a long random secret on a public server.

## Checkout and Telegram

An OpenCart view event swaps in `single_page.twig`, retaining native AJAX-loaded checkout fragments. Districts load after province selection, wards load after district selection, and choosing a ward saves the selection then refreshes shipping rates. Company, address line 2, postcode and fax are removed from the visible checkout fields.

Telegram notifications run after order history changes. Configure the bot token and chat ID in Admin. Delivery uses a short-timeout HTTPS cURL request; failures are logged without interrupting order processing.

## Operational notes

- OpenCart's VND currency and Vietnamese language must be installed and selected in Store settings.
- No OpenCart core file is modified by this extension.
