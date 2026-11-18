# Specialty Tech Store: Dark Neon UI Plan

This specification follows the DJI, creator, gaming gear, and PC component brief. NEXORA is active as the OpenCart store name, with Vietnamese storefront labels, VND pricing, and the imported DJI, gaming, and PC catalog.

## Design system

- Canvas `#05070D`; card `#0C121C`; elevated surface `#131B28`; border `#1C2C40`.
- Main text `#E8F3FF`; secondary text `#8EA4BD`; cyan action `#2EE7FF`; sale `#FF4D6D`; success `#3EE0A0`; warning `#FFB020`.
- Use local system fonts with Rajdhani/Chakra Petch fallbacks for headings. No remote font dependency is required.
- Cards use a 12–16px radius, thin blue-gray border, and a restrained cyan hover glow. Controls have visible focus rings and 44px touch targets on mobile.
- Show a sale badge only when OpenCart returns a special price; ratings only when review data exists; stock only from inventory. Do not display authorized, free shipping, sold-count, countdown, or warranty claims until configured and verified.

## Screen wireframes

| Screen | Desktop | Mobile |
| --- | --- | --- |
| Home | Sticky header, specialty hero, real OpenCart content modules, category discovery, configured footer | Search below header, short hero, stacked discovery cards, fixed five-item navigation |
| Category/search | Breadcrumb, child categories, sort/filter controls, four-column cards, pagination | Breadcrumb, two-column cards, native sort controls, filters only when configured |
| Product | Image gallery and product information side-by-side, options, price, cart actions, description/reviews | Gallery first, compact details, sticky add-to-cart bar above bottom navigation |
| Cart | Editable item table, totals, coupon and checkout action | Stacked product rows and totals, full-width checkout action |
| Checkout | Address, configured delivery/payment extensions, order summary | Single-column form; summary must not cover fields |
| Account | OpenCart dashboard and order history links | Single-column account links and order list |
| Compare/wishlist | Existing OpenCart product comparison and saved products | Horizontally scrollable comparison table; stacked wishlist cards |
| Sale/bundle | OpenCart specials listing; bundle builder needs an extension | Compact sale cards; bundle builder unavailable until implemented |

## Customer flows

1. **Guest purchase:** search or browse → product detail → choose required options → add to cart → checkout → choose configured shipping/payment → confirm order.
2. **Signed-in purchase:** sign in/register → browse → wishlist/compare → cart → checkout → account order history.
3. **Support:** contact page or configured phone → support agent. Public order lookup and return/warranty tickets need an extension or custom module before they can be advertised.

## OpenCart capability map

- **Native routes present:** category, product, search, special products, manufacturer, wishlist, compare, cart, checkout, account, reviews, contact, and sitemap.
- **Added in the theme:** dark neon component styling, mobile quick navigation, and AJAX product suggestions backed by OpenCart's catalog search model.
- **Requires admin configuration:** category tree, product filters, featured modules, banners, reviews, currency/tax, COD/bank payment, shipping rates, coupon/voucher rules, store identity and contact details.
- **Needs an extension or separately scoped build:** product bundles/kit builder, OTP/social login, loyalty wallet, public order tracking, returns workflow, Zalo/Messenger chat, FAQ/help center, blog, showroom finder, notification center, and support tickets.

## Seed catalog candidates (not imported)

These 40 entries are planning rows only. Their `DEMO-` codes are placeholders; verify product identity, source, price, images, warranty, and availability before import. No stock, reviews, discounts, specifications, or sales figures are asserted.

| Draft code | Candidate product | Group |
| --- | --- | --- |
| DEMO-DJI-001 | DJI Mini 4 Pro | Flycam |
| DEMO-DJI-002 | DJI Mini 4 Pro Fly More Combo | Flycam |
| DEMO-DJI-003 | DJI Air 3S | Flycam |
| DEMO-DJI-004 | DJI Mavic 3 Pro | Flycam |
| DEMO-DJI-005 | DJI Mavic 3 Pro Cine | Flycam |
| DEMO-DJI-006 | DJI Avata 2 | FPV |
| DEMO-DJI-007 | DJI Neo | Flycam |
| DEMO-DJI-008 | DJI Flip | Flycam |
| DEMO-DJI-009 | DJI RC 2 | Điều khiển |
| DEMO-DJI-010 | DJI Goggles 3 | FPV |
| DEMO-CREATOR-011 | DJI Osmo Pocket 3 | Camera |
| DEMO-CREATOR-012 | DJI Osmo Pocket 3 Creator Combo | Camera |
| DEMO-CREATOR-013 | DJI Osmo Action 5 Pro | Camera |
| DEMO-CREATOR-014 | DJI RS 4 | Gimbal |
| DEMO-CREATOR-015 | DJI RS 4 Mini | Gimbal |
| DEMO-CREATOR-016 | DJI Mic 2 | Âm thanh |
| DEMO-CREATOR-017 | DJI Mic Mini | Âm thanh |
| DEMO-CREATOR-018 | Bộ lọc ND cho camera tương thích | Phụ kiện |
| DEMO-GAME-019 | Bàn phím cơ layout 60% | Gaming gear |
| DEMO-GAME-020 | Bàn phím cơ layout 75% | Gaming gear |
| DEMO-GAME-021 | Bàn phím cơ TKL | Gaming gear |
| DEMO-GAME-022 | Bàn phím cơ layout 96% | Gaming gear |
| DEMO-GAME-023 | Bàn phím cơ full-size | Gaming gear |
| DEMO-GAME-024 | Bộ switch bàn phím cơ | Gaming gear |
| DEMO-GAME-025 | Chuột gaming không dây | Gaming gear |
| DEMO-GAME-026 | Chuột gaming siêu nhẹ | Gaming gear |
| DEMO-GAME-027 | Chuột gaming polling rate cao | Gaming gear |
| DEMO-GAME-028 | Tai nghe gaming không dây | Gaming gear |
| DEMO-GAME-029 | Tai nghe gaming có dây | Gaming gear |
| DEMO-GAME-030 | Lót chuột gaming cỡ lớn | Gaming gear |
| DEMO-GAME-031 | Tay cầm chơi game | Gaming gear |
| DEMO-GAME-032 | Capture card | Streaming |
| DEMO-PC-033 | Case PC ATX | Linh kiện PC |
| DEMO-PC-034 | Case PC micro-ATX | Linh kiện PC |
| DEMO-PC-035 | RAM DDR5 kit 16GB | Linh kiện PC |
| DEMO-PC-036 | RAM DDR5 kit 32GB | Linh kiện PC |
| DEMO-PC-037 | RAM DDR5 kit 64GB | Linh kiện PC |
| DEMO-PC-038 | SSD NVMe 1TB | Linh kiện PC |
| DEMO-PC-039 | SSD NVMe 2TB | Linh kiện PC |
| DEMO-PC-040 | Bộ nguồn PC 750W | Linh kiện PC |

## QA checklist

- Check home, category, search, product, special, cart, checkout, login, wishlist, and compare at 1440px, 1024px, 768px, 390px, and 360px.
- Confirm no horizontal overflow, clipped labels, covered controls, or broken image links.
- Test search loading/results/no-results/error, keyboard navigation, and accessible labels.
- Test product option validation, add/remove/update cart, checkout totals, and configured payment/shipping without submitting a real order.
- Verify every policy, discount, stock label, review, currency, and product claim against configured store data.
- Re-run PHP lint, Twig compile, and route checks after template/controller changes.
