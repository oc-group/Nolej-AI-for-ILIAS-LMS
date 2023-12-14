ILIAS integration plugin for [Nolej AI](https://nolej.io/). A demo is available [on YouTube](https://www.youtube.com/watch?v=knCsFV4bjeY).

Note: this branch is for ILIAS 8. If you have ILIAS 6 or 7, see branch release_7.


## Installation

### Download the plugin

From the ILIAS directory, run:

```sh
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
cd Customizing/global/plugins/Services/Repository/RepositoryObject
git clone https://github.com/oc-group/Nolej-AI-for-ILIAS-LMS.git Nolej
```

### Install the plugin

1. Go into `Administration` -> `Extending ILIAS` -> `Plugins`
2. Look for the plugin "Nolej"
3. Click on `Actions` -> `Install`

### After installation

1. From the ILIAS directory, run:

```sh
composer install --no-dev
```

2. Configure the API Key
   1. Go into `Administration` -> `Extending ILIAS` -> `Plugins`
   2. Look for the plugin "Nolej"
   3. Click on `Actions` -> `Configure`
   4. Write your Nolej API Key
   5. Save

3. Enable the Anonymous Access
   1. Go into `Administration` -> `System Settings and Maintenance` -> `General Settings`
   2. Enable `Anonymous Access`
   3. Save

4. Enable TinyMCE Editor (optional, but recommended)
   1. Go into `Administration` -> `Layout and Navigation` -> `Editing`
   2. Select tab `TinyMCE Editor` -> `General Settings`
   3. Activate the checkbox
   4. Save

5. (Only for ILIAS 8) Open `client.ini.php` file indide the `data` directory and put this below `[server]`:

```
[server]
prevent_super_global_replacement = 1
```
