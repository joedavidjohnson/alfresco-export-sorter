# alfresco-export-sorter
PHP script that takes an Alfresco export and re-creates the document library filenames and folder structure on filesystem

## Alfresco version
The alfresco-export-sorter was developed against Alfresco 3.3.2. I have not tested this with any other versions, so your mileage may vary (any changes to the .acp schema may cause the script not to function).

## alfresco-export-sorter Input
This script expects as input, an Alfresco Site exported using the Alfresco Export tool (org.alfresco.tools.Export), which consists of:
* An [.acp file](https://wiki.alfresco.com/wiki/Export_and_Import_View_Schema "Alfresco documentation on ACP schema") - an XML file that describes the exported site. This file contains the mapping of folders/filenames to exported filenames.
* A directory of exported files

### Alfresco Export tool
Documentation on the Alfresco Export tool can be found [here](https://wiki.alfresco.com/wiki/Export_and_Import_Tools).
Example instructions for Alfresco running on Windows:
  1. Create export.cmd file in [Alfresco Install Directory]\tomcat\webapps\alfresco\WEB-INF

      `set CPATH=../../../lib/*;../../../endorsed/*;lib/*;classes;../../../shared/classes;`
      
     ` "C:\Path\To\Java\bin\java.exe" -Xms128m -Xmx512m -Xss96k -XX:MaxPermSize=160m -classpath %CPATH% org.alfresco.tools.Export -user [Insert Username] -pwd [Insert Password] -store workspace://SpacesStore -path /app:company_home/st:sites/cm:[Insert Sitename] -verbose "C:\Path\To\export\dir\[Insert Sitename].acp"`

  2. Open command prompt and cd to [Alfresco Install Directory]\tomcat\webapps\alfresco\WEB-INF
  3. Execute export.cmd
  
## alfresco-export-sorter Config
The top of alfrescoExportSorter.php has a set of configuration parameters that should be set before running the script:
* SITE_NAME - exported Alfresco site (used in following setup variables)
* INPUT_FILE - path to exported Alfresco .acp file
* INPUT_FOLDER - path to exported Alfresco file directory
* OUTPUT_FOLDER - path to folder where sorted files/folders should be created
* CONTENT_PREFIX - prefix for files from the export system. This comes from the final argument in the above Alfresco export tool command.
* FOLDER_DIVIDER - change this depending on OS. Currently configured for OSX/Linux/Unix
* TEST_MODE - if true, the script will run in a trial mode where the .acp file is processed but no files/directories are actually copied/created. This must be set to false to actually have files copied.

## alfresco-export-sorter Usage
Once the config has been set, the script can be executed via command-line php:
* `php alfrescoExportSorter.php`

## Output
All output will be on stdout -- the script will output any folders that are created and files being copied.

