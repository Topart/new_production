<?php

/** Include PHPExcel_IOFactory */
require_once dirname(__FILE__) . '/../ext/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';

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
        //TODO

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
        $source_dictionary = $this->getHeaderDictionaryFromSheet($source->getActiveSheet());
        $retail_photo_paper_dictionary = $this->getHeaderDictionaryFromSheet($retail_photo_paper);
        $retail_canvas_dictionary  = $this->getHeaderDictionaryFromSheet($retail_canvas);
        $retail_framing_dictionary  = $this->getHeaderDictionaryFromSheet($retail_framing);


        # FRAMING, STRETCHING, MATTING
		# Automatically scan the source column names and store them in an associative array
		# Declare and fill the retail framing table
        $retail_framing_table = array();
        $last_source_row = $source->getActiveSheet()->getHighestRow();
        $i = 0;
        # Scan all the source rows and process the F21066 items only, and only once at the beginning for efficiency
        for($source_line = 2; $source_line <= $last_source_row; $source_line++)
        {
            $primary_vendor_no = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["PrimaryVendorNo"],$source_line)->getValue();
            if ($primary_vendor_no != "F21066" && $primary_vendor_no != "S73068")
                continue;

            $retail_framing_table[$i] = array();
            # Store all the MAS specific fields, which means the majority of them
            foreach($retail_framing_dictionary as $header => $column)
            {
                $retail_framing_table[$i][$header] = $source->getActiveSheet()
                    ->getCellByColumnAndRow($source_dictionary[$header], $source_line)->getValue();
            }

            # Store the spreadsheet retail prices only
            $last_retail_framing_row = $retail_framing->getHighestRow();
            for($k = 2; $k <= $last_retail_framing_row; $k++)
            {
                for($col = 3; $col <= 6; $col++)
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
            $item_code = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["Item Code"], $source_line)->getValue();
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
            $item_code = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["Item Code"], $temp_i)->getValue();

            for($i = 1; $i <= 4; $i++)
            {
                $a = str_replace(" ", "", $source->getActiveSheet()
                    ->getCellByColumnAndRow($source_dictionary["UDF_ALTS$i"], $temp_i)->getValue());
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
        $destination_line = 2;

        $template = PHPExcel_IOFactory::load($templatePath);
        $template->setActiveSheetIndex(0);
        $templateSheet = $template->getActiveSheet();

        /*** BEGIN MAIN PARALLEL WRITE FOR ***/
        while($source_line <= $last_source_row)
        {
            ### Fields variables for each product are all assigned here ###
            $udf_tar = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["UDF_TAR"], $source_line)->getValue();

            # Skip importing items where udf_tar = N
            if ($udf_tar == "N")
            {
				$source_line++;
				continue;
            }

            $primary_vendor_no = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["PrimaryVendorNo"], $source_line)->getValue();

            # Skip importing the framing related items
            if ($primary_vendor_no == "F21066")
            {
                $source_line++;
				continue;
            }

            $item_code = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["Item Code"], $source_line)->getValue();
            $udf_entity_type = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["UDF_ENTITYTYPE"], $source_line)->getValue();

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
            $description = $source->getActiveSheet()
                ->getCellByColumnAndRow($source_dictionary["Description"], $source_line)->getValue();

			$special_character_index = stripos($description, "^");
            if ($special_character_index !== false)
                $description = str_replace("^", "'", $description);

            $udf_pricecode = $this->getSheetValue($source, $source_dictionary["UDF_PRICECODE"], $source_line);

            $udf_paper_size_cm = $this->getSheetValue($source, $source_dictionary["UDF_PAPER_SIZE_CM"], $source_line);
            $udf_paper_size_in = $this->getSheetValue($source, $source_dictionary["UDF_PAPER_SIZE_IN"], $source_line);
            $udf_image_size_cm = $this->getSheetValue($source, $source_dictionary["UDF_IMAGE_SIZE_CM"], $source_line);
            $udf_image_size_in = $this->getSheetValue($source, $source_dictionary["UDF_IMAGE_SIZE_IN"], $source_line);

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

            $udf_alt_size_1 = str_replace(' ', '', $this->getSheetValue($source, $source_dictionary["UDF_ALTS1"], $source_line));
            $udf_alt_size_2 = str_replace(' ', '', $this->getSheetValue($source, $source_dictionary["UDF_ALTS2"], $source_line));
            $udf_alt_size_3 = str_replace(' ', '', $this->getSheetValue($source, $source_dictionary["UDF_ALTS3"], $source_line));
            $udf_alt_size_4 = str_replace(' ', '', $this->getSheetValue($source, $source_dictionary["UDF_ALTS4"], $source_line));

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

            $udf_oversize = $this->getSheetValue($source, $source_dictionary["UDF_OVERSIZE"], $source_line);
			$udf_serigraph = $this->getSheetValue($source, $source_dictionary["UDF_SERIGRAPH"], $source_line);
			$udf_embossed = $this->getSheetValue($source, $source_dictionary["UDF_EMBOSSED"], $source_line);
			$udf_foil = $this->getSheetValue($source, $source_dictionary["UDF_FOIL"], $source_line);
			$udf_metallic_ink = $this->getSheetValue($source, $source_dictionary["UDF_METALLICINK"], $source_line);
			$udf_specpaper = $this->getSheetValue($source, $source_dictionary["UDF_SPECPAPER"], $source_line);

			$udf_orientation = $this->getSheetValue($source, $source_dictionary["UDF_ORIENTATION"], $source_line);
			$udf_new = $this->getSheetValue($source, $source_dictionary["UDF_NEW"], $source_line);
			$udf_dnd = $this->getSheetValue($source, $source_dictionary["UDF_DND"], $source_line);
			$udf_imsource = $this->getSheetValue($source, $source_dictionary["UDF_IMSOURCE"], $source_line);

			$udf_canvas = $this->getSheetValue($source, $source_dictionary["UDF_CANVAS"], $scan_line);
			$udf_rag = $this->getSheetValue($source, $source_dictionary["UDF_RAG"], $scan_line);
			$udf_photopaper = $this->getSheetValue($source, $source_dictionary["UDF_PHOTOPAPER"], $scan_line);
			$udf_poster = $this->getSheetValue($source, $source_dictionary["UDF_POSTER"], $source_line);
			
			$total_quantity_on_hand = $this->getSheetValue($source, $source_dictionary["TotalQuantityOnHand"], $source_line);
			$udf_decal = $this->getSheetValue($source, $source_dictionary["UDF_DECAL"], $scan_line);
			$udf_embellished = $this->getSheetValue($source, $source_dictionary["UDF_EMBELLISHED"], $scan_line);
            $udf_framed = $this->getSheetValue($source, $source_dictionary["UDF_FRAMED"], $source_line);

            $udf_a4pod = $this->getSheetValue($source, $source_dictionary["UDF_A4POD"], $scan_line);
			$udf_custom_size = $this->getSheetValue($source, $source_dictionary["UDF_CUSTOMSIZE"], $scan_line);
			$udf_petite = $this->getSheetValue($source, $source_dictionary["UDF_PETITE"], $scan_line);
			$udf_small = $this->getSheetValue($source, $source_dictionary["UDF_SMALL"], $scan_line);
			$udf_medium = $this->getSheetValue($source, $source_dictionary["UDF_MEDIUM"], $scan_line);
			$udf_large = $this->getSheetValue($source, $source_dictionary["UDF_LARGE"], $scan_line);
			$udf_osdp = $this->getSheetValue($source, $source_dictionary["UDF_OSDP"], $scan_line);

			$udf_limited = $this->getSheetValue($source, $source_dictionary["UDF_LIMITED"], $source_line);
			$udf_copyright = $this->getSheetValue($source, $source_dictionary["UDF_COPYRIGHT"], $source_line);
			$udf_crline = $this->getSheetValue($source, $source_dictionary["UDF_CRLINE"], $source_line);
			$udf_crimage = $this->getSheetValue($source, $source_dictionary["UDF_CRIMAGE"], $source_line);
			$udf_anycustom = $this->getSheetValue($source, $source_dictionary["UDF_ANYCUSTOM"], $scan_line);

			$udf_maxsfcm = $this->getSheetValue($source, $source_dictionary["UDF_MAXSFCM"], $source_line);
			$udf_maxsfin = $this->getSheetValue($source, $source_dictionary["UDF_MAXSFIN"], $source_line);

            /*
            if (!empty($udf_maxsfin))
				udf_maxsfin = $udf_maxsfin
                end
             */

            $udf_attributes = $this->getSheetValue($source, $source_dictionary["UDF_ATTRIBUTES"], $source_line);
			$udf_ratio_dec = $this->getSheetValue($source, $source_dictionary["UDF_RATIODEC"], $source_line);

			$udf_largeos = $this->getSheetValue($source, $source_dictionary["UDF_LARGEOS"], $scan_line);

			$suggested_retail_price = $this->getSheetValue($source, $source_dictionary["SuggestedRetailPrice"], $source_line);
			$udf_eco = $this->getSheetValue($source, $source_dictionary["UDF_ECO"], $source_line);
			$udf_fmaxslscm = $this->getSheetValue($source, $source_dictionary["UDF_FMAXSLSCM"], $source_line);
			$udf_fmaxslsin = $this->getSheetValue($source, $source_dictionary["UDF_FMAXSLSIN"], $source_line);
			$udf_fmaxsssin = $this->getSheetValue($source, $source_dictionary["UDF_FMAXSSSIN"], $source_line);
			$udf_fmaxssxcm = $this->getSheetValue($source, $source_dictionary["UDF_FMAXSSXCM"], $source_line);


			$df_colorcode = $this->getSheetValue($source, $source_dictionary["UDF_COLORCODE"], $source_line);
			$udf_framecat = $this->getSheetValue($source, $source_dictionary["UDF_FRAMECAT"], $source_line);
			$udf_prisubnsubcat = $this->getSheetValue($source, $source_dictionary["UDF_PRISUBNSUBCAT"], $source_line);
			$udf_pricolor = $this->getSheetValue($source, $source_dictionary["UDF_PRICOLOR"], $source_line);
			$udf_pristyle = $this->getSheetValue($source, $source_dictionary["UDF_PRISTYLE"], $source_line);
			$udf_rooms = $this->getSheetValue($source, $source_dictionary["UDF_ROOMS"], $source_line);


			$udf_artshop = $this->getSheetValue($source, $source_dictionary["UDF_ARTSHOP"], $source_line);
			$udf_artshopi = $this->getSheetValue($source, $source_dictionary["UDF_ARTSHOPI"], $source_line);
			$udf_artshopl = $this->getSheetValue($source, $source_dictionary["UDF_ARTSHOPL"], $source_line);
			$udf_nollcavail = $this->getSheetValue($source, $source_dictionary["UDF_NOLLCAVAIL"], $source_line);
			$udf_llcroy = $this->getSheetValue($source, $source_dictionary["UDF_LLCROY"], $source_line);
			$udf_royllcval = $this->getSheetValue($source, $source_dictionary["UDF_ROYLLCVAL"], $source_line);


			$udf_f_m_avail_4_paper = $this->getSheetValue($source, $source_dictionary["UDF_FMAVAIL4PAPER"], $source_line);
			$udf_f_m_avail_4_canvas = $this->getSheetValue($source, $source_dictionary["UDF_FMAVAIL4CANVAS"], $source_line);
			$udf_moulding_width = $this->getSheetValue($source, $source_dictionary["UDF_MOULDINGWIDTH"], $source_line);
			$udf_ratiocode = $this->getSheetValue($source, $source_dictionary["UDF_RATIOCODE"], $source_line);
			$udf_marketattrib = $this->getSheetValue($source, $source_dictionary["UDF_MARKETATTRIB"], $source_line);
			$udf_artist_name = $this->getSheetValue($source, $source_dictionary["UDF_ARTIST_NAME"], $source_line);

			

            ### End of Fields variables assignments ###
            /***LINE 612***/

	
        }
        /*** END MAIN PARALLEL WRITE FOR ***/

        
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

		return $image_size_width
    }

    # Example image size = "23 5/8 x 47 1/4"
    protected function compute_image_size_length($original_size_value)
    {
		$image_size_length = 0

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

        return $image_size_length
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

}

