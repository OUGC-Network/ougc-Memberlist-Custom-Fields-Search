![[logo.png]]

<h3 align="center">ougc Member List Advanced Search</h3>

---

<p align="center"> Adds the option to filter members by custom profile fields in the advanced member list page.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
    - [Template Modifications](#template_modifications)
- [Settings](#settings)
    - [File Level Settings](#file_level_settings)
- [Templates](#templates)
- [Usage](#usage)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

...

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7
- [MyBB-PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── languages
   │ │ ├── english
   │ │ │ ├── ougcCustomFieldsSearch.lang.php
   │ │ │ ├── admin
   │ │ │ │ ├── ougcCustomFieldsSearch.lang.php
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── CustomFieldsSearch
   │ │ │ │ ├── templates
   │ │ │ │ │ ├── .html
   │ │ │ │ │ ├── field.html
   │ │ │ │ │ ├── field_checkbox.html
   │ │ │ │ │ ├── field_exactly.html
   │ │ │ │ │ ├── field_select.html
   │ │ │ │ │ ├── field_select_option.html
   │ │ │ │ │ ├── field_text.html
   │ │ │ │ │ ├── field_textarea.html
   │ │ │ │ │ ├── globalSearchForm.html
   │ │ │ │ │ ├── globalSearchFormSelectOption.html
   │ │ │ │ │ ├── groupsSelect.html
   │ │ │ │ │ ├── groupsSelectOption.html
   │ │ │ │ │ ├── urlDescription.html
   │ │ │ │ ├── settings.json
   │ │ │ │ ├── admin.php
   │ │ │ │ ├── admin_hooks.php
   │ │ │ │ ├── core.php
   │ │ │ │ ├── forum_hooks.php
   │ │ ├── ougcCustomFieldsSearch.php
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from one of the following sources:
    - ...
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.

### Template Modifications <a name = "template_modifications"></a>

The following template edits are required for this plugin to work.

1. Insert `<!--OUGC_MEMBERLISTSEARCH-->` after `{$welcome}` inside the `portal` template.
2. Insert `{$ougcCustomFieldsSearchProfilePrivacyInput}` after `{$awaysection}` inside the `usercp_profile` template.
3. Insert `{$ougcCustomFieldsSearchProfilePrivacyInput}` after `{$awaysection}` inside the `modcp_editprofile`
   template.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Global Settings

- **Disabled Profile Fields** `select`
    - _Select which custom profile fields to ignore from the advanced search feature. Users will not be able to search
      by these selected fields._
- **Bypass Disabled Profile Fields** `select`
    - _Select which groups are allowed to bypass the above setting._
- **Cache Interval** `numeric` Default: `600`
    - _Select the seconds search queries should be kept into the cache before being deleted. This is useful the most the
      change on users count, frequency of updates of custom profile fields, and search criteria remain constant. The
      larger the search criteria and users' data variations, it is recommended to keep this to a low seconds count._
- **Enable Global Search** `yesNo`
    - _If you enable this, a global search form wil be added to templates._
- **Global Search Fields** `checkBox`
    - _Select the allowed fields to search for in the global search form._
- **Global Search Custom Fields** `select`
    - _Select the allowed custom fields to search for in the global search form._

### File Level Settings <a name = "file_level_settings"></a>

Additionally, you can force your settings by updating the `SETTINGS` array constant in
the `ougc\CustomFieldsSearch\Core` namespace in the `./inc/plugins/ougcCustomFieldsSearch.php` file. Any setting set
this way will always bypass any front-end configuration. Use the setting key as shown below:

```PHP
define('ougc\CustomFieldsSearch\Core\SETTINGS', [
    'key' => 'value',
]);
```

[Go up to Table of Contents](#table_of_contents)

## 📐 Templates <a name = "templates"></a>

The following is a list of templates available for this plugin. Uncommon in plugins, we use some templates exclusively
for the
_Administrator Control Panel_.

- `ougccustomfsearch`
    - _front end_; used when building the search page field rows
- `ougccustomfsearch_field`
    - _front end_; used when building each searchable custom field
- `ougccustomfsearch_field_checkbox`
    - _front end_; used when building a checkbox custom field
- `ougccustomfsearch_field_exactly`
    - _front end_; used when building the exactly search criteria input field
- `ougccustomfsearch_field_select`
    - _front end_; used when building a select custom field
- `ougccustomfsearch_field_select_option`
    - _front end_; used when building a select option
- `ougccustomfsearch_field_text`
    - _front end_; used when building a text custom field
- `ougccustomfsearch_field_textarea`
    - _front end_; used when building a text area custom field
- `ougccustomfsearch_globalSearchForm`
    - _front end_; used when building global search form
- `ougccustomfsearch_globalSearchFormSelectOption`
    - _front end_; used when building a select option in the global search form
- `ougccustomfsearch_groupsSelect`
    - _front end_; used when building a group select input field in the search page
- `ougccustomfsearch_groupsSelectOption`
    - _front end_; used when building a group select option in the search page
- `ougccustomfsearch_urlDescription`
    - _front end_; used when building search notification bar

[Go up to Table of Contents](#table_of_contents)

## 📖 Usage <a name="usage"></a>

This plugin has no additional configurations; after activating make sure to modify the global settings in order to get
this plugin working.

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-159249.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)