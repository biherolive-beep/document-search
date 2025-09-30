# File Manager with Elasticsearch

A WordPress plugin that provides a public-facing file manager with full-text search capabilities powered by Elasticsearch.

## Description

This plugin allows you to designate a directory on your server, indexes the files within it using Elasticsearch, and provides a simple shortcode to display a file tree and search bar on any page of your WordPress site. This is ideal for sites that need to make a large number of documents (like PDFs, DOCX, TXT files) easily searchable for public users.

## Installation

There are two ways to install this plugin: from a pre-packaged release or from the source code.

### From a Pre-packaged Release (Recommended for most users)

1.  Download the latest `.zip` file from the releases page.
2.  In your WordPress admin dashboard, go to **Plugins > Add New > Upload Plugin**.
3.  Upload the `.zip` file and activate the plugin.

### From Source (For Developers)

#### 1. Server Prerequisites

This plugin requires a server with **Apache2**, **PHP**, and **Elasticsearch**. An installation script is provided to help set up a compatible environment on Debian or Ubuntu systems.

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    ```
2.  **Run the server installation script:**
    ```bash
    cd <repository-directory>
    sudo bash install.sh
    ```
    This script will:
    *   Install Apache2, PHP, and necessary extensions (including `php-cli` and `php-curl`).
    *   Install Elasticsearch.
    *   Install the `ingest-attachment` plugin for Elasticsearch, which is required for file content extraction.
    *   Enable and start the necessary services.
3.  **Follow the on-screen instructions** provided by the script to set up a database and install WordPress.

#### 2. Build the Plugin

This plugin uses Composer to manage its dependencies. A build script is provided to make this easy.

1.  **Install Composer:**
    If you don't have Composer installed, run the following command in the plugin's root directory:
    ```bash
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php && php -r "unlink('composer-setup.php');"
    ```
2.  **Run the build script:**
    This will download the required libraries into a `vendor` directory.
    ```bash
    ./build.sh
    ```

#### 3. Plugin Activation

1.  **Copy the Plugin Directory:**
    *   Copy the `wp-file-manager-elasticsearch` directory into your WordPress `wp-content/plugins/` directory.

2.  **Activate the Plugin:**
    *   Navigate to the "Plugins" page in your WordPress admin dashboard.
    *   Find "File Manager with Elasticsearch" and click "Activate".

## Configuration

1.  **Navigate to the Settings Page:**
    *   In the WordPress admin sidebar, go to **File Manager ES**.

2.  **Configure Elasticsearch:**
    *   **Elasticsearch Host:** The hostname or IP address of your Elasticsearch server (e.g., `localhost`).
    *   **Elasticsearch Port:** The port your Elasticsearch server is running on (usually `9200`).

3.  **Configure File Directory:**
    *   **Directory to Index:** The absolute server path to the directory containing the files you want to make searchable. By default, this is set to your WordPress uploads directory.

4.  **Save your settings.**

## Usage

### Displaying the File Manager and Search

To display the file manager and search interface on your site, add the following shortcode to any page or post:

`[file_manager_search]`

*   **Without a search query**, this will render a hierarchical tree of the files and folders in your configured directory.
*   **With a search query**, it will display a list of matching files, including highlighted snippets from the file content that match your search term.

### Indexing Your Files

Before you can search, you must index your files. You can do this in two ways:

#### Manual Re-indexing

1.  Navigate to the **File Manager ES** settings page in your WordPress admin panel.
2.  Under the **Indexing Actions** section, click the **Force Re-index** button.
3.  This will delete all existing data from the Elasticsearch index and start a fresh scan of your directory. A status message will inform you when the process is complete.

#### Automatic Daily Re-indexing

You can also have the plugin automatically re-index your files on a daily basis.

1.  On the **File Manager ES** settings page, go to the **Automation Settings** section.
2.  Check the box for **Automatic Daily Re-indexing**.
3.  Save your settings.

The plugin will now use WP-Cron to schedule a daily re-indexing of your files, ensuring your search results are always up-to-date.