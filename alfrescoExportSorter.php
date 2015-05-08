<?php

/**
 * alfrescoExportSorter.php
 * @author joejohnson
 * @version 1.0
 * 
 * This script takes an input .acp file describing an Alfresco site and associated folder of files
 * and restores the document library folder structure/file names in the output folder as they appear in Alfresco.
 * 
 * This was developed against output from the packaged org.alfresco.tools.Export tool in Alfresco 3.3.2.
 */

///////////////////////
//SETUP

/** alfresco site name -- used in below setup varibles */
$SITE_NAME = 'testsite';

/** input .acp file */
$INPUT_FILE = "./AlfrescoExport/$SITE_NAME.acp";

/** input folder containing files referenced in $INPUT_FILE */
$INPUT_FOLDER = "./AlfrescoExport/$SITE_NAME";

/** folder where files/folders should be restored to */
$OUTPUT_FOLDER = "./AlfrescoSorted/$SITE_NAME";

/** 
 * prefix for files from the export system
 * this comes from the <cm:content> tag inside the <view:propertie> for a file
 * the prefix is normally the export path given to the Alfresco export utility
 * example: <cm:content>contentUrl=D:\export\testsite\export8203946683682898448.pdf|mimetype=application/pdf|size=175820|encoding=utf-8|locale=en_US_</cm:content>
 */
$CONTENT_PREFIX = 'contentUrl=D:\\export\\'.$SITE_NAME.'\\';

/** folder divider character -- currently set for OSX/UNIX */
$FOLDER_DIVIDER = '/';

/** if this is true, the script will run in sim mode -- no folders/files will be copied */
$TEST_MODE = true;

///////////////////////
//FUNCTIONS

/**
 * Process a node:
 * if it's a folder, create the folder in the output directory and process that folder's children
 * if it's content, get the exported path/filename and the original filename, and copy the exported file to the output directory with the original filename
 * 
 * @param SimpleXMLElement $parent parent XML node
 * @param string $folderPath parent folder path, if relevant -- use to build folder path as the method recurses
 */
function processParent(SimpleXMLElement $parent, $folderPath="") {
	global $FOLDER_DIVIDER;
	global $CONTENT_PREFIX;
	global $INPUT_FOLDER;
	global $OUTPUT_FOLDER;
	global $TEST_MODE;
	
	//get the node type
	$parentType = $parent->getName();
	
	if($parentType == 'folder') {		
		//get the folder name and new path
		$folderName = $parent->children("view", true)->properties->children("cm", true)->name;
		$folderNameWithPath = $folderPath.$FOLDER_DIVIDER.$folderName;
		$outputFolder = $OUTPUT_FOLDER.$folderNameWithPath;
		
		//get the folder's children nodes
		//Full path = $parent->children("view", true)->associations->children("cm", true)->contains->children("cm", true);
		//use if-block to verify each level down exists, if it doesn't we'll do nothing
		$folderChildren = null;
		if($parentView = $parent->children("view", true)) {
			if($parentViewAssociations = $parentView->associations) {
				if($parentViewAssociationsCm = $parentViewAssociations->children("cm", true)) {
					if($parentViewAssociationsCmContains = $parentViewAssociationsCm->contains) {
						if($parentViewAssociationsCmContainsCm = $parentViewAssociationsCmContains->children("cm", true)) {
							$folderChildren = $parentViewAssociationsCmContainsCm;
						}
					}
				}
			}
		}
		
		
		echo "processing $parentType: $folderNameWithPath\n";
		echo "\tcreating directory: $outputFolder\n\n";
		
		//create the folder in the $OUTPUT_FOLDER
		if(!$TEST_MODE) {
			if(!mkdir($outputFolder)) {
				echo "Error creating directory!\n";
			}
		}
		
		if(!empty($folderChildren)) {
			//recurse: process each of the folder's children
			foreach($folderChildren as $child) {
				processParent($child, $folderNameWithPath);
			}
		}
	} else if($parentType == 'content') {
		//get the file name and combine with the $OUTPUT_FOLDER
		$contentName = $parent->children("view", true)->properties->children("cm", true)->name;
		$contentNameWithPath = $folderPath.$FOLDER_DIVIDER.$contentName;
		$outputPath = $OUTPUT_FOLDER.$contentNameWithPath;
		
		//get the exported file name and combine with the $INPUT_FOLDER
		$contentDescr = $parent->children("view", true)->properties->children("cm", true)->content;
		$contentDescArr = explode("|", $contentDescr);
		$contentPathFull = $contentDescArr[0];
		$contentPath = $INPUT_FOLDER.$FOLDER_DIVIDER.substr($contentPathFull, strlen($CONTENT_PREFIX));	
		
		echo "processing $parentType:\n\tcopying: $contentPath\n\tto: $outputPath\n\n";
		
		//copy the file into the $OUTPUT_FOLDER
		if(!$TEST_MODE) {
			if(!copy($contentPath, $outputPath)) {
				echo "Error copying file!\n";
			}
		}
		
	} else {
		echo "Unknown node type: $parentType\n";
	}
}

///////////////////////
//EXECUTE

if(file_exists($INPUT_FILE)) {
	$fileContents = file_get_contents($INPUT_FILE);
	if(!empty($fileContents)) {
		$xml = simplexml_load_file($INPUT_FILE);
		
		//this dumps all registered namespaces
		//$namespaces = $result[0]->getNamespaces(true);
		//var_dump($namespaces);
		
		//register namespaces
		$xml->registerXPathNamespace('cm', 'http://www.alfresco.org/model/content/1.0');
		$xml->registerXPathNamespace('view', 'http://www.alfresco.org/view/repository/1.0');
		$xml->registerXPathNamespace('st', 'http://www.alfresco.org/model/site/1.0');
		$xml->registerXPathNamespace('sys', 'http://www.alfresco.org/model/system/1.0');
		$xml->registerXPathNamespace('rn', 'http://www.alfresco.org/model/rendition/1.0');
		$xml->registerXPathNamespace('lnk', 'http://www.alfresco.org/model/linksmodel/1.0');
		$xml->registerXPathNamespace('dl', 'http://www.alfresco.org/model/datalist/1.0');
		
		//create output folder
		echo "\tcreating directory: $OUTPUT_FOLDER\n\n";
		if(!$TEST_MODE) {
			if(!mkdir($OUTPUT_FOLDER)) {
				echo "Error creating directory!\n";
			}
		}
		
		//get the document library node - this is the starting point for the processing
		$result = $xml->xpath("/view:view/cm:folder[@view:childName='cm:documentLibrary']");
		
		processParent($result[0]); //should only be one document library node
		
	} else {
		exit("Empty file: $INPUT_FILE");
	}
} else {
	exit("Failed to open $INPUT_FILE.");
}

?>