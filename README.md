# ![Icon](templates/images/icon_xnlj.svg) Nolej AI for ILIAS LMS
ILIAS integration plugin for [Nolej AI](https://nolej.io/).
A demo is available [on YouTube](https://www.youtube.com/watch?v=knCsFV4bjeY).

## Introduction
Nolej AI, developed by Neuronys, offers several advantages, including AI-driven
courseware that can quickly convert documents, videos, and audio into dynamic
active learning content. It facilitates skill development and personalized
learning paths, saving educators significant time and enhancing engagement through
interactive content creation tools, gamification, and social learning.

To use the plugin, start by [registering on Nolej AI](https://live.nolej.io/signup) website.
Upon registration, you'll receive an API key. Simply insert this key into the plugin to
enable its features. It's a quick step to enhance your experience. Let's get started! :rocket:

## :globe_with_meridians: Supported languages
Help us expand language support! If your favorite language isn't here, contribute and make this project truly global.
Your input is not just welcome - it's essential! :rocket:

This plugin currently supports the following languages:

* :uk: English
* :it: Italian

## Requirements
Note: this branch is for ILIAS 8. If you have ILIAS 6 or 7,
see [branch release_7](https://github.com/oc-group/Nolej-AI-for-ILIAS-LMS/tree/release_7).

* ILIAS 8.x
* [H5P Repository plugin](https://github.com/srsolutionsag/H5P) installed and updated (tested wih version `5.0.11`).

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
composer du
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

5. (Only for ILIAS 8) Open `client.ini.php` file inside the `data` directory and put this below `[server]`:

```
[server]
prevent_super_global_replacement = 1
```
