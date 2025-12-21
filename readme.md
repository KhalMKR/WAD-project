
# Setup Instructions

## Prerequisites
- XAMPP installed and running
- PHP 7.4+ enabled
- MySQL service active

## Database Setup
**Important:** You must import the database first for this application to work. check the sql file in the 'db to import' folder

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database or select an existing one
3. **Important** Name the database as unimerch_hub
4. Click **Import** and select the database file from the project
5. Execute the import

## Getting Started

1. **Clone/Download** the project to `C:/xampp/htdocs/wad_project/`
2. **Configure** database credentials in your config file (if needed)
3. **Start XAMPP** - ensure Apache and MySQL are running
4. **Access** the application at `http://localhost/wad_project/`
5. **Test** the application in your browser

## Troubleshooting
- Ensure MySQL service is running before accessing the app
- Check database connection settings if errors occur
- Verify file permissions in the project directory

## After Updating
1. Export your database to replace the one in the 'db to import' folder
2. git add
3. git commit
4. git push
5. Rinse and Repeat


## Extra Info for logging in as admin
email: admin@unimhub.com
password:admin123