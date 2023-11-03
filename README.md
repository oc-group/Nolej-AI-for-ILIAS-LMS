# ![Icon](templates/images/icon_xnlj.svg) Nolej
ILIAS integration plugin for Nolej.

## Installation

### Download the plugin in the right directory

From the ILIAS directory, run:

```sh
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
cd Customizing/global/plugins/Services/Repository/RepositoryObject
git clone https://github.com/oc-group/Nolej.git Nolej
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
