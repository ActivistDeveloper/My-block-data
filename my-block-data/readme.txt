=== My Block Data ===
Contributors: custom
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
License: GPL-2.0-or-later

Manage multiple independent "datasets" of date/headline/links blocks, each
embeddable on its own WordPress page via a shortcode. Add blocks manually
through the admin UI, or import many at once from a plain-text file.

== Installation ==

1. Zip contents (or upload the `my-block-data` folder directly) to
   `wp-content/plugins/`.
2. Activate "My Block Data" under Plugins in wp-admin.
3. A new "Block Data" menu appears in the admin sidebar with three screens:
   Datasets, Blocks, and Import.

== Concepts ==

* **Dataset** — one independent collection of blocks, with its own intro
  text and slug. This corresponds to one of your "n different pages".
* **Block** — one entry: a date, a headline, and one or more links.

== Usage ==

1. Go to **Block Data → Datasets** and create a dataset (title + optional
   intro text). A slug is generated automatically from the title (or set
   your own).
2. Add blocks either:
   - Manually, under **Block Data → Blocks** (pick the dataset, fill in
     date / headline / links, use "+ Add another link" for more than one).
   - By import, under **Block Data → Import**: paste text or upload a
     `.txt` file, and choose which dataset to add the blocks to (or create
     a new one on the fly). Imported blocks are always appended — nothing
     existing is overwritten, unless you explicitly tick the box to replace
     the intro text with the imported one.
3. On any WordPress page or post, add the shortcode:

   [myblockdata slug="your-dataset-slug"]

   This renders the dataset's intro text followed by all of its blocks,
   each showing its date, headline, and links.

== Import file format ==

Intro text (optional, goes at the very top, before the first block)...

(2024/01/15) Headline for the first block
https://example.com/link-1
https://example.com/link-2

(2024/01/20) Headline for the second block
https://example.com/only-link

The date must be wrapped in round parentheses in YYYY/MM/DD format, e.g.
(2024/01/15). Month/day may be 1 or 2 digits, e.g. (2024/1/5).

A new block starts as soon as a line matching this pattern is found;
everything after the date on that same line becomes the headline. Lines
until the next date line (or blank lines) are treated as links — lines
that don't look like URLs are skipped and reported in the import summary.

== Uninstall ==

Deleting the plugin (not just deactivating it) permanently drops its
database tables and all stored data. Deactivating alone keeps your data
intact.
