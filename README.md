<p align="center">
  <img src="https://www.inbs.software/assets/img/logo-pa.svg" alt="PanelAlpha Logo" width="200">
</p>

<h3 align="center">Gain Advantage With Full WordPress Automation</h3>

---

# Sample Hosting Server Integration

## About

The `Sample Hosting Server Integration` provides a sample implementation of a hosting server integration in PanelAlpha.

## Supported features

**Manage Hosting Accounts**
- List All Hosting Accounts
  - Find Hosting Account  
  - Create Hosting Account
  - Update Hosting Account
  - Delete Hosting Account
  - Suspend/Unsuspend Account
  - Change Account Plan
  - Get Account Configuration
  - Update Account Configuration
- **Manage Domains assigned to Hosting Account**
    - List All Domains
    - Find Domain
    - Create Domain Alias
    - Delete Domain Alias
    - Change Primary Domain
    - Get SSL Certificate Information
    - List Domain Aliases
- **Manage WordPress Applications**
    - Install WordPress Application
    - Clone WordPress Application from existing instance
    - Install WordPress from Template
    - Update WordPress Version
    - Delete WordPress Application
    - Create Staging Environment
    - Push Changes to Production
    - Get Application Statistics
    - Get Available Updates
    - Export Application as ZIP
- **File Management**
    - Upload Files
    - Download Files
    - Create Directories
    - Remove Files and Directories
    - Copy/Move Files
    - Extract ZIP Archives
    - Synchronize Files
- **Database Management (MySQL)**
    - Create Database
    - Delete Database
    - List Databases
    - Get Database Configuration
- **SFTP Account Management**
    - List SFTP Accounts
    - Create SFTP Account
    - Update SFTP Account Password
    - Delete SFTP Account
- **SSL Certificate Management**
    - Get Domain SSL Certificate Information
    - Check Certificate Installation Status
- **PHP Configuration**
    - List Available PHP Versions
    - Get Current PHP Version
    - Update PHP Version
- **Log Management**
    - List Available Log Files
    - Get Webserver Log Content
- **Backup Management**
    - Create Manual Backups
    - List Available Backups
    - Restore from Backup
    - Delete Backup
- **Usage Monitoring**
    - Get Disk Usage Statistics
    - Get Bandwidth Usage Statistics
    - Get Monthly Visitor Statistics
    - Get Account Usage Limits

Ensure all features listed are implemented within the integration to provide comprehensive functionality.

## Creating and Installing Your Own Integration

To create and install your own hosting server integration, follow these steps:

1. **Download the Sample Integration**  
   Start by downloading or cloning the `Sample Hosting Server Integration` as a base template.
2. **Apply Required Changes**  
   Every reference of "SampleHostingServer" should be replaced with your integration name, e.g.
    - rename file app/Lib/Integrations/HostingServers/**SampleHostingServer.php** to **MyHostingProvider.php**
    - replace **class SampleHostingServer** with **class MyHostingProvider** in file
        * app/Lib/Integrations/HostingServers/MyHostingProvider.php
    - rename directory app/Lib/Integrations/HostingServers/**SampleHostingServer** to **MyHostingProvider**
    - rename file app/Lib/Apis/**SampleHostingServerApi.php** to **MyHostingProviderApi.php**
    - replace **class SampleHostingServerApi** with **class MyHostingProviderApi** in file
        * app/Lib/Apis/MyHostingProviderApi.php
    - replace namespace App\Lib\Integrations\HostingServers\\**SampleHostingServer** with **MyHostingProvider** in all files within the **MyHostingProvider** directory:
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Application.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Application/Backups.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/CronJobs.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Dns.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Dns/Records.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Domains.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Email.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Email/Domain.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Email/Domain/Account.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Email/Domain/Forwarder.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/FileManager.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/FtpAccounts.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Logs.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Mysql.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Mysql/Databases.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Mysql/Privileges.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Mysql/Users.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/Php.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/SftpAccounts.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Account/SslCerts.php
        * app/Lib/Integrations/HostingServers/MyHostingProvider/Php.php
    - update the API class reference in app/Lib/Integrations/HostingServers/MyHostingProvider.php:
        * replace **use App\Lib\Apis\SampleHostingServerApi;** with **use App\Lib\Apis\MyHostingProviderApi;**
        * replace all occurrences of **SampleHostingServerApi** with **MyHostingProviderApi** in the file
3. **Upload the Integration**  
   Upload contents of `app` directory to `/opt/panelalpha/app/packages/api/app` directory on the server where PanelAlpha is installed
4. **Add language**
    Add translation from `resources/lang/en/integrations.php` to main app `/opt/panelalpha/app/packages/api/resources/lang/en/integrations.php`.
5. **Activate the Integration**  
   Run following command as root on the server where PanelAlpha is installed:
      ```
      docker compose -f /opt/panelalpha/app/docker-compose.yml exec api php artisan integrations:sync
      ```
6. **Replace example code with your own within all methods in all files**
   Refer to comments inside the files for details.

## License

This repository is licensed under
the [MIT License](https://github.com/panelalpha/sample-hosting-server-integration/blob/main/LICENSE).