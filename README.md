# Food for Blocksy (development preview)

This is a **Blocksy child theme**, not a standalone theme. Install the [Blocksy](https://wordpress.org/themes/blocksy/) parent theme first. The shop requires [WooCommerce](https://wordpress.org/plugins/woocommerce/). The theme shows administrators an opt-in WooCommerce installation link; it never installs plugins or imports content without consent.

## Local test and demo setup

Upload `blocksy-child.zip` in **Appearance → Themes → Add New → Upload Theme**, activate **Food for Blocksy**, then install and activate WooCommerce. Go to **Appearance → Food store setup → Create demo store**. This opt-in creates a homepage, shop page if missing, sample food categories, and eight sample products. It does not delete existing products or automatically import on activation. Demo products are tagged with `_food_demo_product` so they can be identified for cleanup.

The demo homepage showcases the hero, categories, and featured products. The shop/category listings include price range and sorting filters. Product pages use the theme's custom layout. The theme is still an early preview: test it on a disposable Local site, especially homepage/shop assignment and add-to-cart flows.

## Release roadmap (not done yet)

- Separate persistent product-option pricing, metadata, and custom review logic into a companion plugin so data survives switching themes. Audit cart/checkout compatibility (including Cart/Checkout blocks), validation, and review moderation before a public release.
- Move product-option pricing, metadata, and custom review functionality into a companion plugin so store data survives theme changes. Test option pricing with Cart/Checkout blocks and variable products.
- Remove remaining globally injected inline scripts and external CDN dependencies. Minimize WooCommerce template overrides and test simple, variable, grouped, sale, out-of-stock, and virtual products.
- Improve demo setup with licensed food images and a cleanup flow. Never import demo data automatically on theme activation.
- Finish theme review: original branding/screenshot, licensed assets, consistent translation domain and translated strings, keyboard access, RTL/mobile checks, Theme Check, and live checkout tests. Choose whether to distribute independently or submit to WordPress.org; check their current theme-review rules first.

## Official references

- [WordPress child themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [WordPress theme review guidelines](https://developer.wordpress.org/themes/releasing-your-theme/theme-review-guidelines/) and [required guidelines / plugin territory](https://make.wordpress.org/themes/handbook/review/required/#plugin-territory)
- [WooCommerce classic theme developer handbook](https://developer.woocommerce.com/docs/theming/theme-development/classic-theme-developer-handbook/) (theme support, hooks, template overrides)
- [WooCommerce sample data import](https://woocommerce.com/document/importing-woocommerce-sample-data/)
