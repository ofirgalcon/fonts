Fonts module
==============

Lists data on fonts installed on the system.

The script runs `system_profiler SPFontsDataType` in the logged-in console user's context and excludes `/System/Library/` paths.
This means the module reports non-system fonts (for example `/Library/Fonts` and user fonts such as `~/Library/Fonts` when available).

Table Schema
---

* name - varchar(255) - name of the font file
* enabled - int - 0/1 is the font file enabled
* type_name - varchar(255) - typeface name
* fullname - varchar(255) - full name of font
* type_enabled - int - 0/1 typeface enabled
* valid - int - 0/1 font is valid
* duplicate - int - 0/1 duplicate font
* path - text - full path to font file
* type - varchar(255) - font file type
* family - varchar(255) - family group
* style - vharchar(255) - style of typeface
* version - varchar(255) - version of font
* embeddable - int - 0/1 is font embeddable
* outline - int 0/1
* unique_id - varchar(255) - unique ID of font
* copyright - text - copyright information about the font
* copy_protected - int - 0/1 copy protected
* description - text - short description about font
* vendor - text - font vendor
* designer - text - designer of the font
* trademark - text - font trademark
