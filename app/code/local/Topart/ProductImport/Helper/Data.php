<?php

/** Include PHPExcel_IOFactory */
require_once dirname(__FILE__) . '/../ext/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';

/** Include Magmi **/
require_once (dirname(__FILE__) . "/../ext/magmi-git-master/magmi/inc/magmi_defs.php");
require_once (dirname(__FILE__) . "/../ext/magmi-git-master/magmi/integration/inc/magmi_datapump.php");

/**
 * Define a logger class that will receive all magmi logs *
 */
class MagmiLogger
{
    public function log($data, $type)
    {
        echo "$type:$data\n";
    }
}


class Topart_ProductImport_Helper_Data extends Topart_ProductImport_Helper_Abstract
{
    
	protected $_sourceModel		= 'productimport/source';

	public function getConfig()
	{
		return Mage::getSingleton('productimport/config');
    }

    public function getTempDir()
    {
        $dir = Mage::getBaseDir('upload') . DS . 'topart_productimport' . DS;
        mkdir($dir);
        return $dir;
    }

    public function getLatestInputFile($baseName)
    {
        $latestFile = null;
        foreach(glob($this->getTempDir() . $baseName . "_*") as $filename)
        {
            $latestFile = $filename;
        }

        return $latestFile;
    }

    protected function fetchOrUploadFile($baseName)
    {

        $file = $this->getLatestInputFile($baseName);
        $new = $this->processUploadedFile($baseName, empty($file));
        if (!empty($new))
            $file = $new;

        return $file;        
    }

    protected function processUploadedFile($baseName, $required = false)
    {
        if (isset($_FILES[$baseName]['name']) && $_FILES[$baseName]['name'] != '') {
            try {	
                $uploader = new Varien_File_Uploader($baseName);
                $uploader->setAllowedExtensions(array('xls','xlsx','ods'));
                $uploader->setAllowRenameFiles(false);
                $uploader->setFilesDispersion(false);
                $path = $this->getTempDir();
                $fileName = $baseName . "_" . microtime(true);
                $uploader->save($path, $fileName);
                
                return $path . DS . $fileName;
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError('Error loading file:' . $e->getMessage());
                throw $e;
            }
        }
        else if ($required)
        {
            Mage::getSingleton('core/session')->addError('Missing required file: ' . $baseName);
            throw new Exception("Missing file");
        }

        return null;
    }

    public function process()
    {
        echo "START TIME: " . microtime(true) . "\r\n<br />";

        /***
         * Load/Upload Files
         */
        try
        {
            $sourcePath = $this->fetchOrUploadFile('topart_productimport_file_source');
            $templatePath = $this->fetchOrUploadFile('topart_productimport_file_template');
            $retailPath = $this->fetchOrUploadFile('topart_productimport_file_retail');

            if (!file_exists($sourcePath))
            {
                Mage::getSingleton('core/session')->addError('Missing required file: ' . $sourcePath);
                throw new Exception("Missing file");
            }
            if (!file_exists($templatePath))
            {
                Mage::getSingleton('core/session')->addError('Missing required file: ' . $templatePath);
                throw new Exception("Missing file");
            }
            if (!file_exists($retailPath))
            {
                Mage::getSingleton('core/session')->addError('Missing required file: ' . $retailPath);
                throw new Exception("Missing file");
            }
        }
        catch(Exception $e)
        {
            return;
        }

        //load into Excel parser
        $source = PHPExcel_IOFactory::load($sourcePath);
        $source->setActiveSheetIndex(0);
        $sourceSheet = $source->getActiveSheet();
        //load template file
        $template = PHPExcel_IOFactory::load($templatePath);
        $template->setActiveSheetIndex(0);
        //load retail sheets
        $retail = PHPExcel_IOFactory::load($retailPath);
        $retail->setActiveSheetIndex(0);
        $retail_photo_paper = $retail->getSheet(0);
        $retail_canvas = $retail->getSheet(2);
        $retail_framing = $retail->getSheet(3);


        //process headers from each sheet
        $template_dictionary = $this->getHeaderDictionaryFromSheet($template->getActiveSheet());
        $source_dictionary = $this->getHeaderDictionaryFromSheet($sourceSheet);
        $retail_photo_paper_dictionary = $this->getHeaderDictionaryFromSheet($retail_photo_paper);
        $retail_canvas_dictionary  = $this->getHeaderDictionaryFromSheet($retail_canvas);
        $retail_framing_dictionary  = $this->getHeaderDictionaryFromSheet($retail_framing);

        //parse into arrays for speed
        $retail_photo_paper_arr = $this->parseSheetIntoArray($retail_photo_paper, $retail_photo_paper_dictionary);
        $retail_canvas_arr = $this->parseSheetIntoArray($retail_canvas, $retail_canvas_dictionary);
        $sourceSheet_arr = $this->parseSheetIntoArray($sourceSheet, $source_dictionary);

        # FRAMING, STRETCHING, MATTING
		# Automatically scan the source column names and store them in an associative array
		# Declare and fill the retail framing table
        $retail_framing_table = array();
        $last_source_row = $sourceSheet->getHighestRow();
        $i = 0;
        # Scan all the source rows and process the F21066 items only, and only once at the beginning for efficiency
        for($source_line = 2; $source_line <= $last_source_row; $source_line++)
        {
            $primary_vendor_no = $sourceSheet_arr[$source_line][$source_dictionary["PrimaryVendorNo"]];
            if ($primary_vendor_no != "F21066" && $primary_vendor_no != "S73068")
                continue;

            $retail_framing_table[$i] = array();
            # Store all the MAS specific fields, which means the majority of them
            foreach($retail_framing_dictionary as $header => $column)
            {
                $retail_framing_table[$i][$header] = $sourceSheet_arr[$source_line][$source_dictionary[$header]];
            }

            # Store the spreadsheet retail prices only
            $last_retail_framing_row = $retail_framing->getHighestRow();
            for($k = 2; $k <= $last_retail_framing_row; $k++)
            {
                //TODO: these columns are hard coded - cols C - F
                for($col = 2; $col <= 5; $col++)
                {
                    $cell_content = $retail_framing->getCellByColumnAndRow($col, 1)->getValue();

                    if ($retail_framing_table[$i]["Item Code"] == $retail_framing
                        ->getCellByColumnAndRow($retail_framing_dictionary["Item Code"], $k)->getValue())
                    {
                        $retail_framing_table[$i][$cell_content] = $retail_framing
                            ->getCellByColumnAndRow($col, $k)->getValue();
                    }
                }
            }
            $i++;
        }

        //print_r($retail_framing_table);

        $global_alternate_size_array = array();

        # Load a hash table with all the item codes from the products spreadsheet. Used to check the presence of DGs and corresponding posters
        $item_source_line = array();
        for($source_line = 2; $source_line <= $last_source_row; $source_line++)
        {
            $item_code = $sourceSheet_arr[$source_line][$source_dictionary["Item Code"]];
            $item_source_line[$item_code] = $source_line;
        }

        # We use the following hash table to track DG products that should contain the additional poster size as a custom option
		$posters_and_dgs_hash_table = array();
		$poster_only_hash_table = array();
        $template_counter = 1;


        $start = 2;
        $finish = $last_source_row; //TODO: this was hardcoded to 14400, changing to the end of the source input
        #pre_process_alternate_sizes
        $temp_i = $start;
        $temp_x = $finish;

        while ($temp_i <= $temp_x)
        {
            $item_code = $sourceSheet_arr[$temp_i][$source_dictionary["Item Code"]];

            for($i = 1; $i <= 4; $i++)
            {
                $a = str_replace(" ", "", $sourceSheet_arr[$temp_i][$source_dictionary["UDF_ALTS$i"]]);
                if (!empty($a))
                    $global_alternate_size_array[] = $a;
            }

            # If the current sku is an alternate size of a sku we have already met, then skip it and go to the next item number
            if (in_array($item_code, $global_alternate_size_array))
            {
                //p item_code + " already scanned."
                //TODO: this seems like a bug - not sure why we'd add it again if it's already in the array - no effect anyway
                $global_alternate_size_array[] = $item_code;
            }

            $temp_i++;
        }

        //TODO:
        //pre_process_alternate_sizes(2, 14400)
		//t1 = Thread.new{parallel_write(14402, $source.last_row)}
		#t1 = Thread.new{parallel_write(2, 10)}
		#t1 = Thread.new{parallel_write(9020, 9030)}
		#t1 = Thread.new{parallel_write(2, 51)}
		#t2 = Thread.new{parallel_write(52, 101)}
		#t3 = Thread.new{parallel_write(102, 151)}
		#t4 = Thread.new{parallel_write(152, 201)}
		//t1.join
		#t2.join
		#t3.join
		#t4.join

		//puts "The overall running time has been #{Time.now - $beginning} seconds."

		# Accessing this view launch the service automatically
		//respond_to do |format|
	    //		format.html # index.html.erb
		//end

        //parallel_write
        $source_line = 2;
        $destination_line = 2;

        $template = PHPExcel_IOFactory::load($templatePath);
        $template->setActiveSheetIndex(0);
        $templateSheet = $template->getActiveSheet();

        //keep array of items to be imported
        $importItems = array();

        /*** BEGIN MAIN PARALLEL WRITE FOR ***/
        //TODO: TEMPORARILY ONLY DO SUBSET
        ///echo "ORIGINAL LAST SOURCE ROW:" . $last_source_row . "\r\n<br />";
        //$last_source_row = 10;
        while($source_line <= $last_source_row)
        {
            ///echo "$source_line TIME: " . microtime(true) . "\r\n<br />";

            ### Fields variables for each product are all assigned here ###
            $udf_tar = $sourceSheet_arr[$source_line][$source_dictionary["UDF_TAR"]];

            # Skip importing items where udf_tar = N
            if ($udf_tar == "N")
            {
				$source_line++;
				continue;
            }

            $primary_vendor_no = $sourceSheet_arr[$source_line][$source_dictionary["PrimaryVendorNo"]];

            # Skip importing the framing related items
            if ($primary_vendor_no == "F21066")
            {
                $source_line++;
				continue;
            }

            $item_code = $sourceSheet_arr[$source_line][$source_dictionary["Item Code"]];
            $udf_entity_type = $sourceSheet_arr[$source_line][$source_dictionary["UDF_ENTITYTYPE"]];


            # If the current sku is an alternate size of a sku we have already met, then skip it and go to the next item number
		    if (in_array($item_code, $global_alternate_size_array))
            {
                if ($item_source_line[$item_code . "DG"] == ($source_line + 1))
                    $source_line += 2;
                else
                    $source_line += 1;

                # Check if we need to write to csv file now
                if ( $item_code == "XWL4870" )
                {
                    //***LINE 418***//
                    //TODO: this would normally output the csv
                }

                continue;
            }

            # We use this variable to keep track of the right line to take data from.
            $scan_line = 0;

            if ($udf_entity_type == "Poster")
            {
                # Compute the correspondig DG item code
                $dg_item_code = $item_code . "DG";
                # If the poster has a corresponding DG item available
                if ($item_source_line[$dg_item_code])
                {
                    $posters_and_dgs_hash_table[$dg_item_code] = "true";
                    # This will be the line of the corresponding DG item, used for the DG specific attributes only.
                    $scan_line = $item_source_line[$dg_item_code];
                }
                else
                {
                    $poster_only_hash_table[$item_code] = "true";
                    $scan_line = $source_line;
                }
            }

            if ($udf_entity_type == "Image")
            {
                $scan_line = $item_source_line[$item_code];
            }

            /***LINE 469***/
            $description = $sourceSheet_arr[$source_line][$source_dictionary["Description"]];

			$special_character_index = stripos($description, "^");
            if ($special_character_index !== false)
                $description = str_replace("^", "'", $description);

            $udf_pricecode = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PRICECODE"], $source_line);

            $udf_paper_size_cm = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PAPER_SIZE_CM"], $source_line);
            $udf_paper_size_in = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PAPER_SIZE_IN"], $source_line);
            $udf_image_size_cm = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_IMAGE_SIZE_CM"], $source_line);
            $udf_image_size_in = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_IMAGE_SIZE_IN"], $source_line);

            if (empty($udf_paper_size_in) && !empty($udf_paper_size_cm))
                $udf_paper_size_in = round($this->compute_image_size_width($udf_paper_size_cm) / 2.54, 2) . " x " .
                round($this->compute_image_size_length($udf_paper_size_cm) / 2.54, 2);

            if (empty($udf_image_size_in))
            {
				if (!empty($udf_image_size_cm))
                    $udf_image_size_in = round($this->compute_image_size_width(udf_image_size_cm) / 2.54, 2) . " x " .
                    round($this->compute_image_size_length($udf_image_size_cm) / 2.54, 2);
				else
                    $udf_image_size_in = $udf_paper_size_in;
            }

            $udf_alt_size_1 = str_replace(' ', '', $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ALTS1"], $source_line));
            $udf_alt_size_2 = str_replace(' ', '', $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ALTS2"], $source_line));
            $udf_alt_size_3 = str_replace(' ', '', $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ALTS3"], $source_line));
            $udf_alt_size_4 = str_replace(' ', '', $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ALTS4"], $source_line));

            # Array containing the alternate sizes, to be used later in the code
			$alternate_size_array = array();
            if (!empty($udf_alt_size_1))
            {
                $alternate_size_array[] = $udf_alt_size_1;
                $global_alternate_size_array[] = $udf_alt_size_1;
            }
            if (!empty($udf_alt_size_2))
            {
                $alternate_size_array[] = $udf_alt_size_2;
                $global_alternate_size_array[] = $udf_alt_size_2;
            }
            if (!empty($udf_alt_size_3))
            {
                $alternate_size_array[] = $udf_alt_size_3;
                $global_alternate_size_array[] = $udf_alt_size_3;
            }
            if (!empty($udf_alt_size_4))
            {
                $alternate_size_array[] = $udf_alt_size_4;
                $global_alternate_size_array[] = $udf_alt_size_4;
            }

            $udf_oversize = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_OVERSIZE"], $source_line);
			$udf_serigraph = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_SERIGRAPH"], $source_line);
			$udf_embossed = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_EMBOSSED"], $source_line);
			$udf_foil = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FOIL"], $source_line);
			$udf_metallic_ink = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_METALLICINK"], $source_line);
			$udf_specpaper = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_SPECPAPER"], $source_line);

			$udf_orientation = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ORIENTATION"], $source_line);
			$udf_new = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_NEW"], $source_line);
			$udf_dnd = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_DND"], $source_line);
			$udf_imsource = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_IMSOURCE"], $source_line);

			$udf_canvas = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_CANVAS"], $scan_line);
			$udf_rag = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_RAG"], $scan_line);
			$udf_photopaper = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PHOTOPAPER"], $scan_line);
			$udf_poster = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_POSTER"], $source_line);
			
			$total_quantity_on_hand = $this->getSheetValue($sourceSheet, $source_dictionary["TotalQuantityOnHand"], $source_line);
			$udf_decal = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_DECAL"], $scan_line);
			$udf_embellished = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_EMBELLISHED"], $scan_line);
            $udf_framed = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FRAMED"], $source_line);

            $udf_a4pod = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_A4POD"], $scan_line);
			$udf_custom_size = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_CUSTOMSIZE"], $scan_line);
			$udf_petite = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PETITE"], $scan_line);
			$udf_small = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_SMALL"], $scan_line);
			$udf_medium = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_MEDIUM"], $scan_line);
			$udf_large = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_LARGE"], $scan_line);
			$udf_osdp = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_OSDP"], $scan_line);

			$udf_limited = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_LIMITED"], $source_line);
			$udf_copyright = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_COPYRIGHT"], $source_line);
			$udf_crline = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_CRLINE"], $source_line);
			$udf_crimage = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_CRIMAGE"], $source_line);
			$udf_anycustom = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ANYCUSTOM"], $scan_line);

			$udf_maxsfcm = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_MAXSFCM"], $source_line);
			$udf_maxsfin = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_MAXSFIN"], $source_line);

            /*
            if (!empty($udf_maxsfin))
				udf_maxsfin = $udf_maxsfin
                end
             */

            $udf_attributes = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ATTRIBUTES"], $source_line);
			$udf_ratio_dec = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_RATIODEC"], $source_line);

			$udf_largeos = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_LARGEOS"], $scan_line);

			$suggested_retail_price = $this->getSheetValue($sourceSheet, $source_dictionary["SuggestedRetailPrice"], $source_line);
			$udf_eco = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ECO"], $source_line);
			$udf_fmaxslscm = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAXSLSCM"], $source_line);
			$udf_fmaxslsin = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAXSLSIN"], $source_line);
			$udf_fmaxsssin = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAXSSSIN"], $source_line);
			$udf_fmaxssxcm = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAXSSXCM"], $source_line);


			$df_colorcode = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_COLORCODE"], $source_line);
			$udf_framecat = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FRAMECAT"], $source_line);
			$udf_prisubnsubcat = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PRISUBNSUBCAT"], $source_line);
			$udf_pricolor = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PRICOLOR"], $source_line);
			$udf_pristyle = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_PRISTYLE"], $source_line);
			$udf_rooms = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ROOMS"], $source_line);


			$udf_artshop = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ARTSHOP"], $source_line);
			$udf_artshopi = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ARTSHOPI"], $source_line);
			$udf_artshopl = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ARTSHOPL"], $source_line);
			$udf_nollcavail = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_NOLLCAVAIL"], $source_line);
			$udf_llcroy = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_LLCROY"], $source_line);
			$udf_royllcval = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ROYLLCVAL"], $source_line);


			$udf_f_m_avail_4_paper = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAVAIL4PAPER"], $source_line);
			$udf_f_m_avail_4_canvas = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_FMAVAIL4CANVAS"], $source_line);
			$udf_moulding_width = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_MOULDINGWIDTH"], $source_line);
			$udf_ratiocode = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_RATIOCODE"], $source_line);
			$udf_marketattrib = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_MARKETATTRIB"], $source_line);
			$udf_artist_name = $this->getSheetValue($sourceSheet, $source_dictionary["UDF_ARTIST_NAME"], $source_line);


            //echo 'doing item ' . $item_code . "<br/>";

            ### End of Fields variables assignments ###
            /***LINE 612***/

            if (!isset($importItems[$destination_line]))
                $importItems[$destination_line] = array();
            $importItems[$destination_line] = array(
                'sku' => $item_code,
                '_attribute_set' => "Topart - Products",
                '_type' => "simple",
                //for Magmi:
                'attribute_set' => "Topart - Products",
            );

            $collections_count = 0;

            if (!isset($importItems[$destination_line + $collections_count]))
                $importItems[$destination_line + $collections_count] = array();

            $importItems[$destination_line + $collections_count]["_category"] = "Artists/" . $udf_artist_name;
			$importItems[$destination_line + $collections_count]["_root_category"] = "Root Category";
				
            $collections_count++;


            # Category structure: categories and subcategories
			# Example: x(a;b;c).y.z(f).
            $category_array = explode(".", $udf_prisubnsubcat);

            for ($i = 0; $i < count($category_array); $i++)
            {
                if (empty($category_array[$i]))
                    continue;

                if (!isset($importItems[$destination_line + $collections_count]))
                    $importItems[$destination_line + $collections_count] = array();

                $open_brace_index = stripos($category_array[$i], "(");
                $close_brace_index = stripos($category_array[$i], ")");

                /*** LINE 637 ***/

                # Category name
                if ($open_brace_index !== FALSE)
                {
                    $category_name = ucwords(substr($category_array[$i], 0, $open_brace_index));

                    # Subcategory list
                    $subcategory_array = explode(";", substr($category_array[$i], $open_brace_index + 1, $close_brace_index - ($open_brace_index + 1)));

                    for ($j = 0; $j < count($subcategory_array); $j++)
                    {
                        # This if block is only used once to comput the unique list of categories/subcategories
						#if !written_categories.include?(category_name + "/" + subcategory_array[j].capitalize)
						#if !written_categories.include?(category_name)
							#p category_name + "/" + subcategory_array[j].capitalize
							#written_categories << (category_name + "/" + subcategory_array[j].capitalize)
							#written_categories << (category_name)
						#end

                        $importItems[$destination_line + $collections_count]["_category"] = "Subjects/" . $category_name . "/" . ucwords($subcategory_array[$j]);
						$importItems[$destination_line + $collections_count]["_root_category"] = "Root Category";

                        $collections_count++;
                    }
                }
                else
                {
                    $category_name = substr($category_array[$i], 0, strlen($category_array[$i]));

                    # This if block is only used once to comput the unique list of categories/subcategories
					#if !written_categories.include?(category_name)
						#p category_name
						#written_categories << category_name
					#end

					$importItems[$destination_line + $collections_count]["_category"] = "Subjects/" . $category_name;
					$importItems[$destination_line + $collections_count]["_root_category"] = "Root Category";

                    $collections_count++;
                }
            }

            /*** LINE 680 ***/

            # Collections
            $collections_array = explode(".", $udf_marketattrib);

            for ($i = 0; $i < count($collections_array); $i++)
            {
                if (empty($collections_array[$i]))
                    continue;

                if (!isset($importItems[$destination_line + $collections_count]))
                    $importItems[$destination_line + $collections_count] = array();

				$collection_name = substr($collections_array[$i], 0, strlen($collections_array[$i]));

				$importItems[$destination_line + $collections_count]["_category"] = "Collections/" . $collection_name;
				$importItems[$destination_line + $collections_count]["_root_category"] = "Root Category";

                $collections_count++;
            }

            # Rooms
			$rooms_array = explode(".", $udf_rooms);

            for ($i = 0; $i < count($rooms_array); $i++)
            {
                if (!isset($importItems[$destination_line + $i]))
                    $importItems[$destination_line + $i] = array();

                $room_name = substr($rooms_array[$i], 0, strlen($rooms_array[$i]));
                $importItems[$destination_line + $i]["udf_rooms"] = $room_name;
            }

            if (!isset($importItems[$destination_line]))
                $importItems[$destination_line] = array();

            /*** LINE 705 ***/

            $importItems[$destination_line]["_product_websites"] = "base";
			
			# Alt Size 1, Alt Size 2, Alt Size 3, Alt Size 4
			$importItems[$destination_line]["alt_size_1"] = $udf_alt_size_1;
			$importItems[$destination_line]["alt_size_2"] = $udf_alt_size_2;
			$importItems[$destination_line]["alt_size_3"] = $udf_alt_size_3;
			$importItems[$destination_line]["alt_size_4"] = $udf_alt_size_4;

            # ItemCodeDesc
			$importItems[$destination_line]["description"] = $description;

			$importItems[$destination_line]["enable_googlecheckout"] = "1";
			$importItems[$destination_line]["udf_orientation"] = $udf_orientation;

			$importItems[$destination_line]["udf_image_size_cm"] = $udf_image_size_cm;
			$importItems[$destination_line]["udf_image_size_in"] = $udf_image_size_in;
			$importItems[$destination_line]["udf_ratiocode"] = $udf_ratiocode;
			
			$importItems[$destination_line]["meta_description"] = $description;
			$keywords_list = strtolower($udf_attributes);
			$importItems[$destination_line]["meta_keyword"] = $keywords_list;
            $importItems[$destination_line]["meta_title"] = $description;

            /*** LINE 733 ***/

            $importItems[$destination_line]["msrp_display_actual_price_type"] = "Use config";
			$importItems[$destination_line]["msrp_enabled"] = "Use config";

			$importItems[$destination_line]["name"] = $description;
			$importItems[$destination_line]["options_container"] = "Block after Info Column";

			if ($udf_oversize == "Y")
				$importItems[$destination_line]["udf_oversize"] = "Yes";
			else
				$importItems[$destination_line]["udf_oversize"] = "No";

            /*** LINE 746 ***/

            $importItems[$destination_line]["udf_paper_size_cm"] = $udf_paper_size_cm;
			$importItems[$destination_line]["udf_paper_size_in"] = $udf_paper_size_in;

			if ($udf_a4pod == "Y")
				$importItems[$destination_line]["udf_a4pod"] = "Yes";
			else
				$importItems[$destination_line]["udf_a4pod"] = "No";

			$importItems[$destination_line]["price"] = "0.0";

			if ($udf_entity_type == "Image")
				$importItems[$destination_line]["required_options"] = "1";
			else
				$importItems[$destination_line]["required_options"] = "0";

			$importItems[$destination_line]["short_description"] = $description;

            /*** LINE 765 ***/

            #Status: enabled (1), disabled (2)
            if ($udf_entity_type == "Image")
            {
                $importItems[$destination_line]["status"] = "1";
                $importItems[$destination_line]["total_quantity_on_hand"] = 0;
            }
            else
            {
                $importItems[$destination_line]["status"] = "1";
                $importItems[$destination_line]["total_quantity_on_hand"] = $total_quantity_on_hand;
            }

			$importItems[$destination_line]["tax_class_id"] = "2";

            /*** LINE 777 ***/

            #udf_anycustom
			if ($udf_anycustom == "Y")
				$importItems[$destination_line]["udf_anycustom"] = "Yes";
			else
				$importItems[$destination_line]["udf_anycustom"] = "No";

			#udf_maxsfcm
			$importItems[$destination_line]["udf_maxsfcm"] = $udf_maxsfcm;
			#udf_maxsfin
			$importItems[$destination_line]["udf_maxsfin"] = $udf_maxsfin;

			#udf_largeos
			if ($udf_largeos == "Y")
				$importItems[$destination_line]["udf_largeos"] = "Yes";
			else
				$importItems[$destination_line]["udf_largeos"] = "No";

            /*** LINE 796 ***/

            #udf_eco
			if ($udf_eco == "Y")
				$importItems[$destination_line]["udf_eco"] = "Yes";
			else
				$importItems[$destination_line]["udf_eco"] = "No";

			$importItems[$destination_line]["udf_fmaxslscm"] = $udf_fmaxslscm;
			$importItems[$destination_line]["udf_fmaxslsin"] = $udf_fmaxslsin;
			$importItems[$destination_line]["udf_fmaxsssin"] = $udf_fmaxsssin;
			$importItems[$destination_line]["udf_fmaxssxcm"] = $udf_fmaxssxcm;


			$importItems[$destination_line]["udf_colorcode"] = $udf_colorcode;
			$importItems[$destination_line]["udf_framecat"] = $udf_framecat;
			$importItems[$destination_line]["udf_pricolor"] = $udf_pricolor;
			$importItems[$destination_line]["udf_pristyle"] = $udf_pristyle;
			#$importItems[$destination_line]["udf_rooms"] = $udf_rooms;

            /*** LINE 816 ***/

            $importItems[$destination_line]["udf_artshopi"] = $udf_artshopi;
			$importItems[$destination_line]["udf_artshopl"] = $udf_artshopl;
			$importItems[$destination_line]["udf_royllcval"] = $udf_royllcval;

			if ($udf_artshop == "Y")
				$importItems[$destination_line]["udf_artshop"] = "Yes";
			else
				$importItems[$destination_line]["udf_artshop"] = "No";

			if ($udf_nollcavail == "Y")
				$importItems[$destination_line]["udf_nollcavail"] = "Yes";
			else
				$importItems[$destination_line]["udf_nollcavail"] = "No";

			if ($udf_llcroy == "Y")
				$importItems[$destination_line]["udf_llcroy"] = "Yes";
			else
				$importItems[$destination_line]["udf_llcroy"] = "No";

            /*** LINE 839 ***/

            if ($udf_f_m_avail_4_paper == "Y")
                $importItems[$destination_line]["udf_f_m_avail_4_paper"] = "Yes";
            else
                $importItems[$destination_line]["udf_f_m_avail_4_paper"] = "No";

            if ($udf_f_m_avail_4_canvas == "Y")
                $importItems[$destination_line]["udf_f_m_avail_4_canvas"] = "Yes";
            else
                $importItems[$destination_line]["udf_f_m_avail_4_canvas"] = "No";

			$importItems[$destination_line]["udf_moulding_width"] = $udf_moulding_width;
			$importItems[$destination_line]["primary_vendor_no"] = $primary_vendor_no;

            /*** LINE 856 ***/

            #udf_canvas
			if ($udf_canvas == "Y")
				$importItems[$destination_line]["udf_canvas"] = "Yes";
			else
				$importItems[$destination_line]["udf_canvas"] = "No";

			#udf_rag
			if ($udf_rag == "Y")
				$importItems[$destination_line]["udf_rag"] = "Yes";
			else
				$importItems[$destination_line]["udf_rag"] = "No";

			#udf_photopaper
			if ($udf_photopaper == "Y")
				$importItems[$destination_line]["udf_photo_paper"] = "Yes";
			else
				$importItems[$destination_line]["udf_photo_paper"] = "No";

			#udf_poster
			if ($udf_poster == "Y")
				$importItems[$destination_line]["udf_poster"] = "Yes";
			else
				$importItems[$destination_line]["udf_poster"] = "No";

			#udf_decal
			if ($udf_decal == "Y")
				$importItems[$destination_line]["udf_decal"] = "Yes";
			else
				$importItems[$destination_line]["udf_decal"] = "No";

            /*** LINE 892 ***/

            #Artist name
			$importItems[$destination_line]["udf_artist_name"] = $udf_artist_name;

			#Copyright
			if ($udf_copyright == "Y")
				$importItems[$destination_line]["udf_copyright"] = "Yes";
			else
				$importItems[$destination_line]["udf_copyright"] = "No";

			#udf_crimage
			$importItems[$destination_line]["udf_crimage"] = $udf_crimage;

			#udf_crline
			$importItems[$destination_line]["udf_crline"] = $udf_crline;

			#udf_dnd
			if ($udf_dnd == "Y")
				$importItems[$destination_line]["udf_dnd"] = "Yes";
			else
				$importItems[$destination_line]["udf_dnd"] = "No";

			#udf_embellished
			if ($udf_embellished == "Y")
				$importItems[$destination_line]["udf_embellished"] = "Yes";
			else
				$importItems[$destination_line]["udf_embellished"] = "No";

			#udf_framed
			if ($udf_framed == "Y")
				$importItems[$destination_line]["udf_framed"] = "Yes";
			else
				$importItems[$destination_line]["udf_framed"] = "No";

			#udf_imsource
			$importItems[$destination_line]["udf_imsource"] = $udf_imsource;

			#udf_new
			if ($udf_new == "Y")
				$importItems[$destination_line]["udf_new"] = "Yes";
			else
				$importItems[$destination_line]["udf_new"] = "No";

			#udf_custom_size
			if ($udf_custom_size == "Y")
				$importItems[$destination_line]["udf_custom_size"] = "Yes";
			else
				$importItems[$destination_line]["udf_custom_size"] = "No";

			#udf_petite
			if ($udf_petite == "Y")
				$importItems[$destination_line]["udf_petite"] = "Yes";
			else
				$importItems[$destination_line]["udf_petite"] = "No";

			#udf_small
			if ($udf_small == "Y")
				$importItems[$destination_line]["udf_small"] = "Yes";
			else
				$importItems[$destination_line]["udf_small"] = "No";

			#udf_medium
			if ($udf_medium == "Y")
				$importItems[$destination_line]["udf_medium"] = "Yes";
			else
				$importItems[$destination_line]["udf_medium"] = "No";

			#udf_large
			if ($udf_large == "Y")
				$importItems[$destination_line]["udf_large"] = "Yes";
			else
				$importItems[$destination_line]["udf_large"] = "No";

			#udf_osdp
			if ($udf_osdp == "Y")
				$importItems[$destination_line]["udf_osdp"] = "Yes";
			else
				$importItems[$destination_line]["udf_osdp"] = "No";

            /*** LINE 981 ***/

            #udf_limited
			if ($udf_limited == "Y")
				$importItems[$destination_line]["udf_limited"] = "Yes";
			else
				$importItems[$destination_line]["udf_limited"] = "No";

			#udf_pricecode
			$importItems[$destination_line]["udf_pricecode"] = $udf_pricecode;

			#udf_ratio_dec
			$importItems[$destination_line]["udf_ratiodec"] = $udf_ratio_dec;  //TODO: was converted to string in Ruby, necessary?

			$importItems[$destination_line]["udf_tar"] = "Yes";
			$importItems[$destination_line]["status"] = "1";

			#URL Key, with the SKU as suffix to keep it unique among products
			$importItems[$destination_line]["url_key"] = str_replace(' ', '-', $description) . "-" . $item_code;

			$importItems[$destination_line]["visibility"] = "4";
			$importItems[$destination_line]["weight"] = "1";

            /*** LINE 1005 ***/

            $importItems[$destination_line]["min_qty"] = "0";
			$importItems[$destination_line]["use_config_min_qty"] = "1";
			$importItems[$destination_line]["is_qty_decimal"] = "0";
			$importItems[$destination_line]["backorders"] = "0";

			$importItems[$destination_line]["use_config_backorders"] = "1";
			$importItems[$destination_line]["min_sale_qty"] = "1";
			$importItems[$destination_line]["use_config_min_sale_qty"] = "1";
			$importItems[$destination_line]["max_sale_qty"] = "0";
			$importItems[$destination_line]["use_config_max_sale_qty"] = "1";

            /*** LINE 1019 ***/

            if ($udf_entity_type == "Image")
            {
				$importItems[$destination_line]["is_in_stock"] = "0";
				$importItems[$destination_line]["use_config_notify_stock_qty"] = "0";
				$importItems[$destination_line]["manage_stock"] = "0";
				$importItems[$destination_line]["use_config_manage_stock"] = "0";
				$importItems[$destination_line]["qty"] = "0";
                $importItems[$destination_line]["has_options"] = "1";
            }
            else
            {
				$importItems[$destination_line]["is_in_stock"] = "1";
				$importItems[$destination_line]["use_config_notify_stock_qty"] = "1";
				$importItems[$destination_line]["manage_stock"] = "0";
				$importItems[$destination_line]["use_config_manage_stock"] = "0";
				$importItems[$destination_line]["qty"] = $total_quantity_on_hand;
                $importItems[$destination_line]["has_options"] = "0";
            }

            /*** LINE 1038 ***/

            $importItems[$destination_line]["stock_status_changed_auto"] = "0";
			$importItems[$destination_line]["use_config_qty_increments"] = "1";
			$importItems[$destination_line]["qty_increments"] = "0";
			$importItems[$destination_line]["use_config_enable_qty_inc"] = "1";
			$importItems[$destination_line]["enable_qty_increments"] = "0";
			$importItems[$destination_line]["is_decimal_divided"] = "0";

            /*** LINE 1048 ***/

            if ($udf_entity_type == "Poster" && ( (($udf_imsource == "San Diego" || $udf_imsource == "Italy") && $total_quantity_on_hand > -1) || $udf_imsource == "Old World"))
            {
				$image_size_width = $this->compute_image_size_width($udf_image_size_in);
				$image_size_length = $this->compute_image_size_length($udf_image_size_in);

				$poster_size_ui = $this->compute_poster_size_ui($image_size_width, $image_size_length);
				$poster_size = $this->compute_poster_size($image_size_width, $image_size_length);

				$importItems[$destination_line]["size_category"] = $this->compute_poster_size_category($poster_size_ui);


				# Embellishments
				if ($udf_metallic_ink == "Y")
					$importItems[$destination_line]["udf_metallic_ink"] = "Yes";
				else
					$importItems[$destination_line]["udf_metallic_ink"] = "No";
				if ($udf_foil == "Y")
					$importItems[$destination_line]["udf_foil"] = "Yes";
				else
					$importItems[$destination_line]["udf_foil"] = "No";
				if ($udf_serigraph == "Y")
					$importItems[$destination_line]["udf_serigraph"] = "Yes";
				else
					$importItems[$destination_line]["udf_serigraph"] = "No";
				if ($udf_embossed == "Y")
					$importItems[$destination_line]["udf_embossed"] = "Yes";
				else
					$importItems[$destination_line]["udf_embossed"] = "No";
				if ($udf_specpaper == "Y")
					$importItems[$destination_line]["udf_specpaper"] = "Yes";
				else
					$importItems[$destination_line]["udf_specpaper"] = "No";
            }

            /*** LINE 1092 ***/

            ########## Custom options columns ##########

			# MATERIAL: paper and canvas are static hard-coded options.

			########### Material ###############

			$importItems[$destination_line]["_custom_option_type"] = "radio";
			$importItems[$destination_line]["_custom_option_title"] = "Material";
			$importItems[$destination_line]["_custom_option_is_required"] = "1";
			$importItems[$destination_line]["_custom_option_max_characters"] = "0";
			$importItems[$destination_line]["_custom_option_sort_order"] = "0";


			# Each material option is displayed according to the corresponding udf values
            if ($udf_entity_type == "Poster" && ( (($udf_imsource == "San Diego" || $udf_imsource == "Italy") && $total_quantity_on_hand > -1) || $udf_imsource == "Old World"))
            {
				if ($udf_limited == "Y")
					$importItems[$destination_line]["_custom_option_row_title"] = "Art Paper";
				else
					$importItems[$destination_line]["_custom_option_row_title"] = "Poster";

				$importItems[$destination_line]["_custom_option_row_price"] = "0.00";
				$importItems[$destination_line]["_custom_option_row_sku"] = "material_posterpaper";
				$importItems[$destination_line]["_custom_option_row_sort"] = "0";

                $destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();
            }

            /*** LINE 1123 ***/

            if ($udf_photopaper == "Y")
            {
				$importItems[$destination_line]["_custom_option_row_title"] = "Paper";
				$importItems[$destination_line]["_custom_option_row_price"] = "0.00";
				$importItems[$destination_line]["_custom_option_row_sku"] = "material_photopaper";
				$importItems[$destination_line]["_custom_option_row_sort"] = "1";

                $destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();
            }

			# If not available as poster only
            if ($udf_canvas == "Y")
            {
				$importItems[$destination_line]["_custom_option_row_title"] = "Canvas";
				$importItems[$destination_line]["_custom_option_row_price"] = "0.00";
				$importItems[$destination_line]["_custom_option_row_sku"] = "material_canvas";
				$importItems[$destination_line]["_custom_option_row_sort"] = "2";

                $destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();
            }

			########### End of Material ###############

            ///echo "$source_line TIME check A: " . microtime(true) . "\r\n<br />";

            /*** LINE 1149 ***/

            #############SIZE#############
			$importItems[$destination_line]["_custom_option_type"] = "radio";
			$importItems[$destination_line]["_custom_option_title"] = "Size";
			$importItems[$destination_line]["_custom_option_is_required"] = "1";
			$importItems[$destination_line]["_custom_option_max_characters"] = "0";
			$importItems[$destination_line]["_custom_option_sort_order"] = "1";
			
			# We need to extract the right prices, looking them up by (i.e. matching) the ratio column

			# Extract and map the border treatments:
			# 1) Scan for every row into the master paper and master canvas sheets
			# 2) check if the ratio matches the one contained in the product attribute 
			# 3) If the 2 ratios match, then copy the specific retail price option

			$match_index = 0;

            /*** LINE 1166 ***/

            ########## IF POSTER IS IN STOCK ####################
			# Change the minimum total quantity on hand when it is ready in MAS, from -1 to 0
			# The poster is available only when it is in stock
            if ($udf_entity_type == "Poster" && ( (($udf_imsource == "San Diego" || $udf_imsource == "Italy") && $total_quantity_on_hand > -1) || $udf_imsource == "Old World"))
            {
				$size_name = "Poster Paper";

				$image_size_width = $this->compute_image_size_width($udf_image_size_in);
				$image_size_length = $this->compute_image_size_length($udf_image_size_in);

				$poster_size = $this->compute_poster_size($image_size_width, $image_size_length);
				$poster_size_ui = $this->compute_poster_size_ui($image_size_width, $image_size_length);


				$importItems[$destination_line]["_custom_option_row_title"] = $size_name . ": " . $poster_size;
				if ($suggested_retail_price != 0)
					$importItems[$destination_line]["_custom_option_row_price"] = $suggested_retail_price;
				else
					$importItems[$destination_line]["_custom_option_row_price"] = "0.0";

				$size_category = strtolower($this->compute_poster_size_category($poster_size_ui));

                $importItems[$destination_line]["_custom_option_row_sku"] = "size_posterpaper_" . $size_category . "_ui_" . intval($poster_size_ui) .
                    "_width_" . intval($image_size_width) . "_length_" . intval($image_size_length);
				$importItems[$destination_line]["_custom_option_row_sort"] = $match_index;

				$destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();

				$match_index++;



				# Extract the alternate sizes here
                if (!empty($alternate_size_array))
                {
                    foreach($alternate_size_array as $i_th_alt_size)
                    {
						$alternate_size_line = $item_source_line[$i_th_alt_size];
                        $alternate_size = $sourceSheet_arr[$alternate_size_line][$source_dictionary["UDF_IMAGE_SIZE_IN"]];

                        if (empty($alternate_size))
                        {
                            $alternate_size = $sourceSheet_arr[$alternate_size_line][$source_dictionary["UDF_PAPER_SIZE_IN"]];
                        }

						# Alternate size parameters: to be passed later in a dedicated function
						$size_name = "Poster Paper";
						
						$image_size_width = $this->compute_image_size_width($alternate_size);
						$image_size_length = $this->compute_image_size_length($alternate_size);

						$poster_size = $this->compute_poster_size($image_size_width, $image_size_length);
						$poster_size_ui = $this->compute_poster_size_ui($image_size_width, $image_size_length);
						
                        $suggested_retail_price = $sourceSheet_arr[$alternate_size_line][$source_dictionary["SuggestedRetailPrice"]];

						$size_category = strtolower($this->compute_poster_size_category($poster_size_ui));

						$importItems[$destination_line]["_custom_option_row_title"] = $size_name . ": " . $poster_size;
						if ($suggested_retail_price != 0)
							$importItems[$destination_line]["_custom_option_row_price"] = $suggested_retail_price;
						else
							$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
                        $importItems[$destination_line]["_custom_option_row_sku"] = "size_posterpaper_" . $size_category . "_altsize_" . strtolower($i_th_alt_size) .
                            "_ui_" . intval($poster_size_ui) . "_width_" . intval($image_size_width) . "_length_" . intval($image_size_length);
						$importItems[$destination_line]["_custom_option_row_sort"] = $match_index;

						$destination_line++;
                        if (!isset($importItems[$destination_line]))
                            $importItems[$destination_line] = array();

						$match_index++;

                    }
                }
            }

			########## end of IF POSTER IS IN STOCK ####################

            /*** LINE 1246 ***/

            ///echo "$source_line TIME check B: " . microtime(true) . "\r\n<br />";

            ########## IF UDF_PHOTOPAPER == Y ####################
            if ($udf_photopaper == "Y")
            {
				# If not available as poster only
                if ($poster_only_hash_table[$item_code] != "true")
                {
					$custom_size_ui_to_skip = 0;
					$min_delta = 1000;

                    ///echo "$source_line TIME check B.1: " . microtime(true) . "\r\n<br />";
                    # First pass: scan all the available UI sizes
                    for ($i = 2; $i <= $retail_photo_paper->getHighestRow(); $i++)
                    {
                        $retail_ratio_dec = floatval($retail_photo_paper_arr[$retail_photo_paper_dictionary["Decimal Ratio"]]);

                        if ($udf_ratio_dec == $retail_ratio_dec)
                        {
                            $size_paper_ui = intval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["UI"]]);

							$delta = $poster_size_ui - $size_paper_ui;
							$delta = abs($delta);

                            if ($delta < $min_delta)
                            {
								$custom_size_ui_to_skip = $size_paper_ui;
								$min_delta = $delta;
                            }
                        }
                    }
                    ///echo "$source_line TIME check B.2: " . microtime(true) . "\r\n<br />";

                    # Master Photo Paper Sheet
                    $maxRow = $retail_photo_paper->getHighestRow();
                    for ($i = 2; $i <= $maxRow; $i++)
                    {
                        $retail_ratio_dec = floatval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Decimal Ratio"]]);
                        //$retail_ratio_dec = (float)($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Decimal Ratio"]]);
                        //continue;
                        $size_photopaper_ui = intval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["UI"]]);
                        $image_source = $retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Image Source"]];

                        $image_length = floatval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Length"]]);
                        $image_width = floatval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Width"]]);

						$short_side = 0;
						if ($image_length < $image_width)
							$short_side = $image_length;
						else
							$short_side = $image_width;


						# Check for available sizes: the poster size replaces the closes photo paper digital size
                        if ($udf_ratio_dec == $retail_ratio_dec && $size_photopaper_ui != $custom_size_ui_to_skip && $udf_imsource == $image_source && (empty($udf_maxsfin) || $short_side <= $udf_maxsfin))
                        {
							#retail_ratio_dec = "#{$retail_photo_paper.cell(i, $retail_photo_paper_dictionary["Decimal Ratio"])}"
                            //$retail_ratio_dec = $retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Decimal Ratio"]];
                            $size_name = $retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Size Description"]];

							$allowed_size = "false";
							
							# Match the right sizes
                            if (($udf_petite == "Y" && $size_name == "Petite") || ($udf_small == "Y" && $size_name == "Small") ||
                                ($udf_medium == "Y" && $size_name == "Medium") || ($udf_large == "Y" && $size_name == "Large") ||
                                ($udf_osdp == "Y" && $size_name == "Oversize") || ($udf_largeos == "Y" && $size_name == "Oversize Large"))
                            {
                                $allowed_size = "true";
                            }

							# If the size is allowed, then create the corresponding option
                            if ($allowed_size == "true")
                            {
                                $size_price = $retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Rolled Paper - Estimated Retail"]];
                                $size_photopaper_length = intval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Length"]]);
                                $size_photopaper_width = intval($retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Width"]]);

								$orientation = strtolower($udf_orientation);
                                if ($orientation == "landscape")
                                {
                                    if ($size_photopaper_width < $size_photopaper_length)
                                    {
										$temp = $size_photopaper_width;
										$size_photopaper_width = $size_photopaper_length;
                                        $size_photopaper_length = $temp;
                                    }
                                }
                                else if ($orientation == "portrait")
                                {
                                    if ($size_photopaper_width > $size_photopaper_length)
                                    {
										$temp = $size_photopaper_width;
										$size_photopaper_width = $size_photopaper_length;
                                        $size_photopaper_length = $temp;
                                    }
                                }
                                else
                                {
                                    # Do nothing, Square Images are set already
                                }

								$size_photopaper_width = (string)$size_photopaper_width;
								$size_photopaper_length = (string)$size_photopaper_length;

								$importItems[$destination_line]["_custom_option_row_title"] = $size_name . ": " . $size_photopaper_width . "\""  . "x" . $size_photopaper_length . "\"";
								$importItems[$destination_line]["_custom_option_row_price"] = $size_price;

								if (strtolower($size_name) == "oversize large")
									$size_name = "Oversize_Large";

				
                                $importItems[$destination_line]["_custom_option_row_sku"] = "size_photopaper_" . strtolower($size_name) . "_ui_" . $size_photopaper_ui .
                                    "_width_" . $size_photopaper_width . "_length_" . $size_photopaper_length;
								$importItems[$destination_line]["_custom_option_row_sort"] = $match_index;

                                $destination_line++;
                                if (!isset($importItems[$destination_line]))
                                    $importItems[$destination_line] = array();

								$match_index++;
                            }
                        }
                    }
                    ///echo "$source_line TIME check B.3: " . microtime(true) . "\r\n<br />";
                }
            }

			########## end IF UDF_PHOTOPAPER == Y ####################

            /*** LINE 1367 ***/
            ///echo "$source_line TIME check C: " . microtime(true) . "\r\n<br />";

            ########## IF UDF_CANVAS == Y ####################
            if ($udf_canvas == "Y")
            {
                # Master Canvas Sheet
                for ($i = 2; $i <= $retail_canvas->getHighestRow(); $i++)
                { 
                    $retail_ratio_dec = floatval($retail_canvas_arr[$i][$retail_canvas_dictionary["Decimal Ratio"]]);
                    $image_source = $retail_photo_paper_arr[$i][$retail_photo_paper_dictionary["Image Source"]];
					
                    $size_canvas_length_1 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["WH_Length"]]);
                    $size_canvas_width_1 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["WH_Width"]]);

					$short_side = 0;
					if ($size_canvas_length_1 < $size_canvas_width_1)
						$short_side = $size_canvas_length_1;
					else
						$short_side = $size_canvas_width_1;
					
					$count = 0;

					# Check for available sizes and border treatments prices
                    if ($udf_ratio_dec == $retail_ratio_dec && $udf_imsource == $image_source && (empty($udf_maxsfin) || $short_side <= $udf_maxsfin))
                    {
                        $size_name = $retail_canvas_arr[$i][$retail_canvas_dictionary["Size Description"]];

                        $size_canvas_length_2 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["BL_Length"]]);
                        $size_canvas_width_2 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["BL_Width"]]);
						
                        $size_canvas_length_3 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["MR_Length"]]);
                        $size_canvas_width_3 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["MR_Width"]]);

						$allowed_size = "false";
							
						# Match the right sizes
                        if (($udf_petite == "Y" && $size_name == "Petite") || ($udf_small == "Y" && $size_name == "Small") ||
                            ($udf_medium == "Y" && $size_name == "Medium") || ($udf_large == "Y" && $size_name == "Large") || 
                            ($udf_osdp == "Y" && $size_name == "Oversize") || ($udf_largeos == "Y" && $size_name == "Oversize Large"))
                        {
                            $allowed_size = "true";
                        }

						# If the size is allowed, then create the corresponding option
                        if ($allowed_size == "true")
                        {
                            $size_price_treatment_1 = $retail_canvas_arr[$i][$retail_canvas_dictionary["Rolled Canvas White Border -  Estimated Retail"]];
                            $size_price_treatment_2 = $retail_canvas_arr[$i][$retail_canvas_dictionary['Rolled Canvas 2" Black Border - Estimated Retail']];
                            $size_price_treatment_3 = $retail_canvas_arr[$i][$retail_canvas_dictionary['Rolled Canvas 2" Mirror Border -  Estimated Retail']];

                            $size_canvas_ui_1 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["WH_UI"]]);
                            $size_canvas_ui_2 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["BL_UI"]]);
                            $size_canvas_ui_3 = intval($retail_canvas_arr[$i][$retail_canvas_dictionary["MR_UI"]]);

							$orientation = strtolower($udf_orientation);
                            if ($orientation == "landscape")
                            {
                                if ($size_canvas_width_1 < $size_canvas_length_1)
                                {
									$this->swap($size_canvas_width_1, $size_canvas_length_1);
									$this->swap($size_canvas_width_2, $size_canvas_length_2);
									$this->swap($size_canvas_width_3, $size_canvas_length_3);
                                }
                            }
                            else if ($orientation == "portrait")
                            {
                                if ($size_canvas_width_1 > $size_canvas_length_1)
                                {
									$this->swap($size_canvas_width_1, $size_canvas_length_1);
									$this->swap($size_canvas_width_2, $size_canvas_length_2);
                                    $this->swap($size_canvas_width_3, $size_canvas_length_3);
                                }
                            }
                            else
                            {
                                # Do nothing, Square Images are set already
                            }

							$size_canvas_width_1 = (string)$size_canvas_width_1;
							$size_canvas_length_1 = (string)$size_canvas_length_1;

							$size_canvas_width_2 = (string)$size_canvas_width_2;
							$size_canvas_length_2 = (string)$size_canvas_length_2;

							$size_canvas_width_3 = (string)$size_canvas_width_3;
							$size_canvas_length_3 = (string)$size_canvas_length_3;
						

							$size_prices = array(
							    $size_price_treatment_1, $size_price_treatment_2, $size_price_treatment_3 );

							$size_canvas_width = array(
							    $size_canvas_width_1, $size_canvas_width_2, $size_canvas_width_3 );

							$size_canvas_length = array(
							    $size_canvas_length_1, $size_canvas_length_2, $size_canvas_length_3 );

							$size_canvas_ui = array(
							    $size_canvas_ui_1, $size_canvas_ui_2, $size_canvas_ui_3 );


                            for ($count = 0; $count <= 2; $count++)
                            {
                                $importItems[$destination_line]["_custom_option_row_title"] = $size_name . ": " . $size_canvas_width[$count] . "\""  . "x" . $size_canvas_length[$count] . "\"";
								$importItems[$destination_line]["_custom_option_row_price"] = $size_prices[$count];

								if (strtolower($size_name) == "oversize large")
									$size_name = "Oversize_Large";

								#_custom_option_row_sku
                                $importItems[$destination_line]["_custom_option_row_sku"] = "size_canvas_" . strtolower($size_name) . "_treatment_" .
                                    ((string)($count+1)) . "_ui_" . (string)$size_canvas_ui[$count] . "_width_" . (string)$size_canvas_width[$count] . "_length_" . (string)$size_canvas_length[$count];
								#_custom_option_row_sort
								$importItems[$destination_line]["_custom_option_row_sort"] = $match_index + $count;

								$destination_line++;
                                if (!isset($importItems[$destination_line]))
                                    $importItems[$destination_line] = array();

								$count = $count;
							
                            }

							$match_index = $match_index + 1 + $count;
                        }
                    }
                }


				# BORDER TREATMENTS for canvas
				# If not available as poster only
                if ($poster_only_hash_table[$item_code] != "true")
                {

					########### Border Treatments ###############
					# Border Treatments and Stretching options (including names) are static

					$importItems[$destination_line]["_custom_option_type"] = "radio";
					$importItems[$destination_line]["_custom_option_title"] = "Borders";
					$importItems[$destination_line]["_custom_option_is_required"] = "1";
					$importItems[$destination_line]["_custom_option_max_characters"] = "0";
					$importItems[$destination_line]["_custom_option_sort_order"] = "2";
					
					$importItems[$destination_line]["_custom_option_row_title"] = "None";
					$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
					$importItems[$destination_line]["_custom_option_row_sku"] = "treatments_none";
					$importItems[$destination_line]["_custom_option_row_sort"] = "0";

					$destination_line++;
                    if (!isset($importItems[$destination_line]))
                        $importItems[$destination_line] = array();
					

					$importItems[$destination_line]["_custom_option_row_title"] = "2\" White Border";
					$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
					$importItems[$destination_line]["_custom_option_row_sku"] = "border_treatment_3_inches_of_white";
					$importItems[$destination_line]["_custom_option_row_sort"] = "1";

					$destination_line++;
                    if (!isset($importItems[$destination_line]))
                        $importItems[$destination_line] = array();


					$importItems[$destination_line]["_custom_option_row_title"] = "2\" Black Border";
					$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
					$importItems[$destination_line]["_custom_option_row_sku"] = "border_treatment_2_inches_of_black_and_1_inch_of_white";
					$importItems[$destination_line]["_custom_option_row_sort"] = "2";

					$destination_line++;
                    if (!isset($importItems[$destination_line]))
                        $importItems[$destination_line] = array();

					$importItems[$destination_line]["_custom_option_row_title"] = "2\" Mirrored Border";
					$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
					$importItems[$destination_line]["_custom_option_row_sku"] = "border_treatment_2_inches_mirrored_and_1_inch_of_white";
					$importItems[$destination_line]["_custom_option_row_sort"] = "3";

					$destination_line++;
                    if (!isset($importItems[$destination_line]))
                        $importItems[$destination_line] = array();
                }


				########### Canvas Stretching ###############
                for ($i = 0; $i <= (count($retail_framing_table) - 2); $i++)
                { 

					$udf_entity_type = $retail_framing_table[$i]["UDF_ENTITYTYPE"];

                    if ($udf_entity_type == "Stretch")
                    {
						$stretch_item_number = strtolower($retail_framing_table[$i]["Item Code"]);
						$stretch_ui_price = $retail_framing_table[$i]["United Inch TAR Retail"];

						$importItems[$destination_line]["_custom_option_type"] = "checkbox";
						$importItems[$destination_line]["_custom_option_title"] = "Canvas Stretching";
						$importItems[$destination_line]["_custom_option_is_required"] = "0";
						$importItems[$destination_line]["_custom_option_max_characters"] = "0";
						$importItems[$destination_line]["_custom_option_sort_order"] = "3";
						
						$stretching_index = 0;

						$importItems[$destination_line]["_custom_option_row_title"] = "1.5\" Canvas Gallery Stretching";
						$importItems[$destination_line]["_custom_option_row_price"] = (string)$stretch_ui_price;
						$importItems[$destination_line]["_custom_option_row_sku"] = $stretch_item_number;
						$importItems[$destination_line]["_custom_option_row_sort"] = $stretching_index;

						$destination_line++;
                        if (!isset($importItems[$destination_line]))
                            $importItems[$destination_line] = array();
						$stretching_index++;
                    }
                }
            }
			########## end of IF UDF_CANVAS == Y ####################

            /*** LINE 1573 ***/

            ########### FRAMING ###########
			
			########## if UDF_FRAMED == Y ####################
            if ($udf_framed == "Y")
            {
				$importItems[$destination_line]["_custom_option_type"] = "drop_down";
				$importItems[$destination_line]["_custom_option_title"] = "Frame";
				$importItems[$destination_line]["_custom_option_is_required"] = "1";
				$importItems[$destination_line]["_custom_option_max_characters"] = "0";
				$importItems[$destination_line]["_custom_option_sort_order"] = "4";

				$frame_count = 0;
				$mats_count = 0;

				# Add the No Frame option
				$importItems[$destination_line]["_custom_option_row_sku"] = "frame_none";
				$importItems[$destination_line]["_custom_option_row_title"] = "No Frame";
				$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
				$importItems[$destination_line]["_custom_option_row_sort"] = $frame_count;

				$destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();
				$frame_count++;

                # Only scan the framing options
                for ($i = 0; $i <= (count($retail_framing_table) - 2); $i++)
                {
					$udf_entity_type = $retail_framing_table[$i]["UDF_ENTITYTYPE"];
					
                    if ($udf_entity_type == "Frame")
                    {
						$frame_name = $retail_framing_table[$i]["Description"];

						$frame_item_number = strtolower($retail_framing_table[$i]["Item Code"]);
						$frame_ui_price = $retail_framing_table[$i]["United Inch TAR Retail"];
						$frame_flat_mounting_price = $retail_framing_table[$i]["Flat Mounting Cost"];

						$frame_for_paper = $retail_framing_table[$i]["UDF_FMAVAIL4PAPER"];
						$frame_for_canvas = $retail_framing_table[$i]["UDF_FMAVAIL4CANVAS"];

						# Scan the category names and add each of them to an array, used to add it only once
						$category_name = strtolower($retail_framing_table[$i]["UDF_FRAMECAT"]);
				

						# Each framing option has a different price for each size (UI) available

						# Available for Paper
                        if ($frame_for_paper == "Y")
                        {
							$importItems[$destination_line]["_custom_option_row_sku"] = $frame_item_number;
							$importItems[$destination_line]["_custom_option_row_title"] = $frame_name;
							$importItems[$destination_line]["_custom_option_row_price"] = (string)$frame_ui_price;
							$importItems[$destination_line]["_custom_option_row_sort"] = $frame_count;

							$destination_line++;
                            if (!isset($importItems[$destination_line]))
                                $importItems[$destination_line] = array();
							$frame_count++;
                        }

						# Available for Canvas
						# Removed for now: framing is not available for canvas for now
                        #if ($frame_for_canvas == "Y")
                        #{

						#	$importItems[$destination_line]["_custom_option_row_sku"] = $frame_item_number;
						#	$importItems[$destination_line]["_custom_option_row_title"] = $frame_name;
						#	$importItems[$destination_line]["_custom_option_row_price"] = (string)$frame_ui_price;
						#	$importItems[$destination_line]["_custom_option_row_sort"] = $frame_count;

						#	$destination_line++;
                        #   if (!isset($importItems[$destination_line]))
                        #      $importItems[$destination_line] = array();
						#	$frame_count++;

						#}

                    }


                }


				


				########### MATTING ###########
				$importItems[$destination_line]["_custom_option_type"] = "radio";
				$importItems[$destination_line]["_custom_option_title"] = "Mat";
				$importItems[$destination_line]["_custom_option_is_required"] = "1";
				$importItems[$destination_line]["_custom_option_max_characters"] = "0";
				$importItems[$destination_line]["_custom_option_sort_order"] = "5";


                for ($i = 0; $i <= (count($retail_framing_table) - 2); $i++)
                {
					$udf_entity_type = $retail_framing_table[$i]["UDF_ENTITYTYPE"];

                    if ($udf_entity_type == "Mat")
                    {
						$mat_name = $retail_framing_table[$i]["Description"];
						$mat_item_number = strtolower($retail_framing_table[$i]["Item Code"]);

						$mat_ui_price = $retail_framing_table[$i]["United Inch TAR Retail"];
						$mats_for_paper = $retail_framing_table[$i]["UDF_FMAVAIL4PAPER"];
						$mats_for_canvas = $retail_framing_table[$i]["UDF_FMAVAIL4CANVAS"];
						$mats_color = $retail_framing_table[$i]["UDF_COLORCODE"];
						$category_name = strtolower($retail_framing_table[$i]["UDF_FRAMECAT"]);


						# Available for Paper
                        if ($mats_for_paper == "Y")
                        {

							$importItems[$destination_line]["_custom_option_row_sku"] = $mat_item_number;
							$importItems[$destination_line]["_custom_option_row_title"] = $mat_name;
							$importItems[$destination_line]["_custom_option_row_price"] = (string)$mat_ui_price;
							$importItems[$destination_line]["_custom_option_row_sort"] = $mats_count;

                            $destination_line++;
                            if (!isset($importItems[$destination_line]))
                                $importItems[$destination_line] = array();
							$mats_count++;
                        }

                    }
                }

				$importItems[$destination_line]["_custom_option_row_sku"] = "mats_none";
				$importItems[$destination_line]["_custom_option_row_title"] = "No Mat";
				$importItems[$destination_line]["_custom_option_row_price"] = "0.0";
				$importItems[$destination_line]["_custom_option_row_sort"] = $mats_count;

				$destination_line++;
                if (!isset($importItems[$destination_line]))
                    $importItems[$destination_line] = array();
				$mats_count++;

            }
			########## end of if UDF_FRAMED == Y ####################

            /*** LINE 1709 ***/

            ####### CUSTOM SIZE: HEIGHT #########
			$importItems[$destination_line]["_custom_option_type"] = "field";
			#_custom_option_title
			$importItems[$destination_line]["_custom_option_title"] = "Height";
			#_custom_option_is_required
			$importItems[$destination_line]["_custom_option_is_required"] = "0";
			#_custom_option_max_characters
			$importItems[$destination_line]["_custom_option_max_characters"] = "0";
			#_custom_option_sort_order
			$importItems[$destination_line]["_custom_option_sort_order"] = "6";

			$destination_line++;
            if (!isset($importItems[$destination_line]))
                $importItems[$destination_line] = array();

			####### CUSTOM SIZE: WIDTH #########
			$importItems[$destination_line]["_custom_option_type"] = "field";
			#_custom_option_title
			$importItems[$destination_line]["_custom_option_title"] = "Width";
			#_custom_option_is_required
			$importItems[$destination_line]["_custom_option_is_required"] = "0";
			#_custom_option_max_characters
			$importItems[$destination_line]["_custom_option_max_characters"] = "0";
			#_custom_option_sort_order
			$importItems[$destination_line]["_custom_option_sort_order"] = "7";

			$destination_line++;
            if (!isset($importItems[$destination_line]))
                $importItems[$destination_line] = array();

            /*** LINE 1746 ***/

            # Compute the maximum count among all the multi select options
			# then add it to the destination line count for the next product to be written
			$custom_options_array_size = 0;

			$multi_select_options = array( $collections_count );

			if ($udf_entity_type == "Image")
				$multi_select_options[] = $custom_options_array_size;

			$max_count =  max( $multi_select_options );
			
			# Increase the destination line to the correct number
			$destination_line = $destination_line + $max_count;
			$destination_line = $destination_line + 1;
            if (!isset($importItems[$destination_line]))
                $importItems[$destination_line] = array();

			//p source_line.to_s + "/" + $source.last_row.to_s
            /*
			if ( ( $source_line % 800 == 0 or ((source_line + 1) % 800 == 0) ) or source_line == last_row - 1 )

				# Finally, fill the template
				template_file_name = "csv/new_inventory_" + $template_counter.to_s + ".csv"
				p "Creating " + template_file_name + "..."
				template.to_csv(template_file_name)

				puts "The running time for the current .csv file has been #{Time.now - $beginning} seconds."

				$template_counter = $template_counter + 1
				destination_line = 2

				# Reset the template file to store the new rows
				template = Openoffice.new("Template_2013_05_10/template.ods")
				template.default_sheet = template.sheets.first
                end
             */

            /***LINE 1785***/
            $source_line = $scan_line + 1;
	
        }
        /*** END MAIN PARALLEL WRITE FOR ***/

        echo "END INITIAL PROCESS TIME: " . microtime(true) . "\r\n<br />";

        /*
        for ($i=0; $i <= 100; $i++)
        {
            print_r($importItems[array_keys($importItems)[$i]]);
        }
         */
        //

        /*** POST PROCESS INTO MAGMI FORMAT ***/
        echo "START POST PROCESS TIME: " . microtime(true) . "\r\n<br />";
        $magmiData = array();
        $currentSku = null;
        $currentSkuRow = array();

        $currentOptionTitle = null;
        $currentOptionColumn = null;
        foreach ($importItems as $line => $row)
        {
            //echo "LINE $line<br/>\r\n";
            if (isset($row['sku']) && !empty($row['sku']))
            {
                $currentSku = $row['sku'];
                //echo "NEW SKU: $currentSku<br/>\r\n";
                $currentSkuRow = $row;
                $magmiData[$currentSku] = $row;

                //categories
                $magmiData[$currentSku]['categories'] = '';
                if (isset($row['_root_category']) && isset($row['_category']))
                {
                    //$magmiData[$currentSku]['categories'] .= $row['_root_category'] . '/' . $row['_category'];
                    $magmiData[$currentSku]['categories'] .= $row['_category'];
                }

                //custom options
                $currentOptionTitle = null;
                $currentOptionColumn = null;
                if (isset($row['_custom_option_type']) && !empty($row['_custom_option_type']) &&
                    isset($row['_custom_option_title']) && !empty($row['_custom_option_title']))
                {
                    $currentOptionTitle = $row['_custom_option_title'];
                    $currentOptionColumn = $row['_custom_option_title'] . ':' . $row['_custom_option_type'];
                    if (isset($row['_custom_option_is_required']))
                        $currentOptionColumn .= ':' . $row['_custom_option_is_required'];
                    if (isset($row['_custom_option_sort_order']))
                        $currentOptionColumn .= ':' . $row['_custom_option_sort_order'];
                    $magmiData[$currentSku][$currentOptionColumn] = '';

                    //record the first row
                    if (isset($row['_custom_option_row_title']) && !empty($row['_custom_option_row_title']))
                    {
                        $magmiData[$currentSku][$currentOptionColumn] .= $row['_custom_option_row_title'] . ':fixed'; //HACK hardcoded fixed

                        if (isset($row['_custom_option_row_price']))
                            $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_price'];
                        else
                            $magmiData[$currentSku][$currentOptionColumn] .= ':0.00';

                        if (isset($row['_custom_option_row_sku']) && !empty($row['_custom_option_row_sku']))
                            $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_sku'];

                        if (isset($row['_custom_option_row_sort']))
                            $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_sort'];
                    }
                }

            }
            else
            {
                //categories
                if (isset($row['_root_category']) && isset($row['_category']))
                {
                    if (!empty($magmiData[$currentSku]['categories']))
                        $magmiData[$currentSku]['categories'] .= ';;';
                    //$magmiData[$currentSku]['categories'] .= $row['_root_category'] . '/' . $row['_category'];
                    $magmiData[$currentSku]['categories'] .= $row['_category'];
                }

                //handle new options
                if (isset($row['_custom_option_type']) && !empty($row['_custom_option_type']) &&
                    isset($row['_custom_option_title']) && !empty($row['_custom_option_title']))
                {
                    $currentOptionTitle = $row['_custom_option_title'];
                    $currentOptionColumn = $row['_custom_option_title'] . ':' . $row['_custom_option_type'];
                    if (isset($row['_custom_option_is_required']))
                        $currentOptionColumn .= ':' . $row['_custom_option_is_required'];
                    if (isset($row['_custom_option_sort_order']))
                        $currentOptionColumn .= ':' . $row['_custom_option_sort_order'];                          
                    $magmiData[$currentSku][$currentOptionColumn] = '';
                }

                //custom option row
                if (!empty($currentOptionColumn) && isset($row['_custom_option_row_title']) && !empty($row['_custom_option_row_title']))
                {
                    if (!empty($magmiData[$currentSku][$currentOptionColumn]))
                        $magmiData[$currentSku][$currentOptionColumn] .= "|";

                    $magmiData[$currentSku][$currentOptionColumn] .= $row['_custom_option_row_title'] . ':fixed'; //HACK hardcoded fixed

                    if (isset($row['_custom_option_row_price']))
                        $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_price'];
                    else
                        $magmiData[$currentSku][$currentOptionColumn] .= ':0.00';

                    if (isset($row['_custom_option_row_sku']) && !empty($row['_custom_option_row_sku']))
                        $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_sku'];

                    if (isset($row['_custom_option_row_sort']) && !empty($row['_custom_option_row_sort']))
                        $magmiData[$currentSku][$currentOptionColumn] .= ':' . $row['_custom_option_row_sort'];
                }         
                
            }

            //clear out the unparsed option data from magmi
            unset($magmiData[$currentSku]['_custom_option_type']);
            unset($magmiData[$currentSku]['_custom_option_title']);
            unset($magmiData[$currentSku]['_custom_option_is_required']);
            unset($magmiData[$currentSku]['_custom_option_max_characters']);
            unset($magmiData[$currentSku]['_custom_option_sort_order']);

            unset($magmiData[$currentSku]['_custom_option_row_title']);
            unset($magmiData[$currentSku]['_custom_option_row_price']);
            unset($magmiData[$currentSku]['_custom_option_row_sku']);
            unset($magmiData[$currentSku]['_custom_option_row_sort']);

            unset($magmiData[$currentSku]['_category']);
            unset($magmiData[$currentSku]['_root_category']);
        }

        echo "END POST PROCESS TIME: " . microtime(true) . "\r\n<br />";

        //print_r($magmiData);
        //


        //do the actual import
        echo "START IMPORT TIME: " . microtime(true) . "\r\n<br />";
        $this->magmiImport($magmiData);
        echo "END IMPORT TIME: " . microtime(true) . "\r\n<br />";
    }

    protected function magmiImport($magmiData)
    {
        /**
         * create a Product import Datapump using Magmi_DatapumpFactory
         */
        $dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
        /**
         * Start import session
         * with :
         * - profile : test_ptj
         * - mode : create
         * - logger : an instance of the class defined above
         */

        /**
         * FOR THE SAMPLE TO WORK CORRECTLY , YOU HAVE TO DEFINE A test_ptj profile with :
         * UPSELL/CROSS SELL, ITEM RELATER, CATEGORIES IMPORTER/CREATOR selected
         * ON THE FLY INDEXER IS RECOMMENDED (better endimport performance)
         * Reindexer needed also to have products show up on front : select all but "catalog_category_product" & "url_rewrite" (both are handled by on the fly indexer)
         */
        $dp->beginImportSession("TopArt-Products", "create", new MagmiLogger());

        foreach($magmiData as $sku => $item)
        {
            //debug append test to item sku
            $item['sku'] .= '-TEST-MAGMI';

            $dp->ingest($item);
        }

        /* end import session, will run post import plugins */
        $dp->endImportSession();
    }

    # Example image size = "23 5/8 x 47 1/4"
    protected function compute_image_size_width($original_size_value)
    {
        $image_size_width = 0;
        if (($xpos = stripos($original_size_value, 'x')) !== false)
            $original_image_size_width = substr($original_size_value, 0, $xpos);

        if (($slashpos = stripos($original_image_size_width, '/')) !== false)
        {
			$width_fraction_numerator = $original_image_size_width[$slashpos - 1];
			$width_fraction_denominator = $original_image_size_width[$slashpos + 1];
			
            $image_size_width += $width_fraction_numerator / $width_fraction_denominator;
        }

		if (stripos($original_image_size_width, '.') !== false)
			$image_size_width += substr($original_image_size_width, 0, 5);
		else
			$image_size_width += substr($original_image_size_width, 0, 2);

		return $image_size_width;
    }

    # Example image size = "23 5/8 x 47 1/4"
    protected function compute_image_size_length($original_size_value)
    {
		$image_size_length = 0;
        if (($xpos = stripos($original_size_value, 'x')) !== false)
            $original_image_size_length = substr($original_size_value, $xpos + 2);

        if (($slashpos = stripos($original_image_size_length, '/')) !== false)
        {
			$length_fraction_numerator = $original_image_size_length[$slashpos - 1];
			$length_fraction_denominator = $original_image_size_length[$slashpos + 1];
			
            $image_size_length += $length_fraction_numerator / $length_fraction_denominator;
        }

        if (stripos($original_image_size_length, '.') !== false)
			$image_size_length += substr($original_image_size_length, 0, 5);
		else
			$image_size_length += substr($original_image_size_length, 0, 2);

        return $image_size_length;
    }

    protected function compute_poster_size($a, $b)
    {
        return intval($a) . "\"" . "x" . intval($b) . "\"";
    }

    protected function compute_poster_size_ui($a, $b)
    {
        return intval($a + $b);
    }

    protected function compute_poster_size_category($x)
    {
        if ($x != 0)
        {
			if ($x < 30) 
				return "XS";

			if ($x >= 30 && $x <  40)
				return "S";

			if ($x >= 40 && $x < 50)
				return "M";

			if ($x >= 50 && $x < 60)
				return "L";

			if ($x >= 60)
				return "XL";
        }

        return "";
    }

    protected function swap(&$x,&$y)
    {
        $tmp=$x;
        $x=$y;
        $y=$tmp;
    }





    protected function getSheetValue($sheet, $col, $row)
    {
        return $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }

    protected function getHeaderDictionaryFromSheet($sheet)
    {
        $template_dictionary = array();
        for($i = 0; $i <= 200; $i++)
        {
            $val = $sheet->getCellByColumnAndRow($i,1)->getValue();
            if (!empty($val))
            {
                //$template_dictionary[$val] = PHPExcel_Cell::stringFromColumnIndex($i);
                $template_dictionary[$val] = $i;
            }
        }

        return $template_dictionary;
    }


    protected function parseSheetIntoArray($sheet, $dictionary)
    {
        $result = array();
        for($i = 1; $i <= $sheet->getHighestRow(); $i++)
        {
            $result[$i] = array();
            foreach($dictionary as $name => $col)
            {
                $value = $sheet->getCellByColumnAndRow($col, $i)->getValue();
                $result[$i][$col] = $value;
            }
        }
        return $result;
    }

}

