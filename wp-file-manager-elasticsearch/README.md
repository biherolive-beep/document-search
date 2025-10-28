# File Manager with Elasticsearch

A WordPress plugin that provides a public-facing file manager with full-text search capabilities powered by Elasticsearch.

## Description

This plugin allows you to designate a directory on your server, indexes the files within it using Elasticsearch, and provides a simple shortcode to display a file tree and search bar on any page of your WordPress site. This is ideal for sites that need to make a large number of documents (like PDFs, DOCX, TXT files) easily searchable for public users.

## Installation

### 1. Server Prerequisites

This plugin requires a server with **Apache2**, **PHP**, and **Elasticsearch**. An installation script is provided to help set up a compatible environment on Debian or Ubuntu systems.

1.  **Download the project.**
2.  **Run the installation script:**
    ```bash
    sudo bash install.sh
    ```
    This script will:
    *   Install Apache2, PHP, and necessary extensions.
    *   Install Elasticsearch.
    *   Install the `ingest-attachment` plugin for Elasticsearch, which is required for file content extraction.
    *   Enable and start the necessary services.
3.  **Follow the on-screen instructions** provided by the script to set up a database and install WordPress.

### 2. Plugin Activation

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

To display the file manager and search interface on your site, add the following shortcode to any page or post:

`[file_manager_search]`

This will render the file explorer, which will eventually include a hierarchical view of your indexed directory and a search bar.

## Next Steps (Development Roadmap)

*   **Phase 2:** Implement file system scanning and display the folder/file tree.
*   **Phase 3:** Implement the Elasticsearch indexing logic.
*   **Phase 4:** Build the search functionality.
*   **Phase 5:** Finalize UI, add cron-based re-indexing, and write comprehensive documentation.