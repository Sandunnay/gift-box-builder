## High-Performance Order Storage (HPOS) Compatibility

The WooCommerce Gift Box Builder plugin is designed to be compatible with WooCommerce's High-Performance Order Storage (HPOS) feature, also known as "Custom Order Tables."

The plugin's core functionality revolves around product selection, gift box configuration using a custom post type, and interaction with the WooCommerce cart. It does not directly create, read, update, or delete order data. Interactions with WooCommerce products and the cart primarily use standard functions and methods that are HPOS-agnostic.

While the plugin uses the `wc_get_refreshed_fragments()` function for cart updates, which is an older mechanism, it is still functional with HPOS. Future updates may align this with newer cart fragment handling methods as part of ongoing best practice improvements.

No specific issues are anticipated when using this plugin in an environment where HPOS is enabled.
