<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Repo {

	protected $wpDB;
	protected $dbPrefix;
	protected $galleries;

	/**
	 * Repo constructor.
	 */
	public function __construct() {
		global $wpdb;
		global $nggdb;

		$this->galleries = $nggdb;
		$this->wpDB      = $wpdb;
		$this->dbPrefix  = $this->wpDB->prefix;

	}

	/**
	 * Delete a customer record.
	 *
	 * @param int $id customer ID
	 */
	public function delete_picture( $pid ) {
		return $this->wpDB->delete(
			$this->tableName(),
			[ 'pid' => $pid ],
			[ '%d' ]
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count($table,$where) {
		$table = $this->dbPrefix.$table;

		$sql = "SELECT COUNT(*) FROM {$table}";
		if($where) $sql .= 'WHERE gid='.$where;
		return $this->wpDB->get_var( $sql );
	}



	/**
	 * @param array $images
	 */
	public function updateImages( $image_id,  $new_alt_text ) {

		if ( ! isset( $_POST[ $image_id ] ) ) {
			$this->flashMessage( 'error-no-img-selected', __('Please select at least one image',NABTXD) );
			return false;
		}

		if ( ! isset( $_POST[$new_alt_text] ) )
		{
			$this->flashMessage( 'error-no-alt', __('please select tag or write something!',NABTXD) );
			return false;

		}
		$new_alt_text = $_POST[$new_alt_text];
		$image_id = esc_sql( $_POST[ $image_id ] );

		$equal_alt_text = $this->compareArray( $image_id, $new_alt_text, true );

		$combined = $this->combineArray( $image_id, $equal_alt_text );

		foreach ( $combined as $pid => $alttext ) {

			$images_query[] = "WHEN {$pid} THEN '{$alttext}' ";
		}

		$table         = $this->tableName();
		$where         = $image_id;
		$whereToString = implode( ',', $where );
		// implode images to string , so we can use them in SQL query
		$emplode_images = implode( ' ', $images_query );

		/** refere to
		 * http://www.karlrixon.co.uk/writing/update-multiple-rows-with-different-values-and-a-single-sql-query/
		 */
		$query = "UPDATE {$table}
                     SET alttext = CASE pid "
		         . $emplode_images .
		         "END
                     WHERE pid IN (" . $whereToString . ")";

		$this->flashMessage('success-random-update','random update successfully done');
		return $this->wpDB->query( $query );

	}

	public function updateSingleRow( $pid, $new_alt ) {

		return $this->wpDB->update(
			$this->tableName(),
			[ 'alttext' => $new_alt ],
			[ 'pid' => $pid ]
		);
	}

	public function updateMultipleItemsBySingleAlt( $ids, $new_alt ) {
		if ( ! isset( $_POST[ $ids ] ) )
		{
			$this->flashMessage( 'error-no-img-selected', __('you forgot to select any images',NABTXD) );
			return false;
		}

		if(! isset( $_POST[$new_alt]))
		{
			$this->flashMessage( 'error-no-alt', __('please select tag or write something!',NABTXD) );
			return false;
		}

		$new_alt = esc_attr($this->arrayToString($_POST[$new_alt]));
		$get_ids = $_POST[ $ids ];

		$ids     = esc_sql( implode( ',', $get_ids ) );
		$sql     = "UPDATE {$this->tableName()}
                    SET alttext = '$new_alt'
                    WHERE pid IN (" . $ids . ")";

		$this->flashMessage('success-alt-update','update successfully done');
		return $this->wpDB->query( $sql );
	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_pictures( $per_page = 5, $page_number = 1,$galleryid = null ) {

		global $wpdb;

		if ( isset( $galleryid ) ) {
			$esc_galleryid = esc_sql($galleryid);
		}

		$sql = "SELECT * FROM {$this->tableName()}";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		if ( isset( $esc_galleryid ) ) {
			$sql .= ' WHERE galleryid = '.$esc_galleryid;
		}
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public function getAllGalleries() {
		$table = esc_sql($this->dbPrefix.'ngg_gallery');

		$sql = 'SELECT gid,name FROM '.$table;
		return $this->wpDB->get_results($sql,'ARRAY_A');
	}

	public function showGalleries() {
		$galleries = self::getAllGalleries();
		echo '<select class="gallery-select" onchange="location = this.options[this.selectedIndex].value;"><option value="?page='.$_REQUEST['page'].'">'.__('all galleris',NABTXD).'</option>';

		foreach($galleries as $gallery)
			{
				$des = sprintf('?page=%s&gid=%s',esc_attr($_REQUEST['page']),$gallery['gid']);
				if ( isset( $_REQUEST['gid'] ) and $_REQUEST['gid'] == $gallery['gid'] ) {
					$selected = 'selected';
				} else $selected = null;

				$link =  '<option class="gallery-option" value="'.$des.'"' . $selected.'>';
				$link .= $gallery['name'].'</option>';

				echo $link;

			}
		echo '</select>';
	}

	public function tableName() {
		$table = $this->dbPrefix . "ngg_pictures";

		return $table;
	}

	public function getAllTags() {
		return get_tags();
	}

	public function showTags( $name,$fail_or_success = true,$num=40 ) {
		$tags = $this->getAllTags();
		if($this->countArray($tags) > 40 )
		{
			$tags = array_slice($tags,0,$num);
		}
		$chunk = array_chunk($tags,5);
		$i = 0;
		echo '<div class="nab-tag-container">';

		foreach ( $chunk as  $row ) {
			$class = ' class="odd-row"';
			if($i++ % 2 == 0) $class = ' class="even-row"';
			echo '<div'. $class.'>';
			foreach($row as $item)
			{
				$input =  '<div class="seo-tag"><input type="checkbox" name="' . $name . '[]" value="' . $item->name . '"';

				if($fail_or_success == false)	$input .= $this->remainValue($name,$item->name);

				$input .= '><span>' . $item->name .'</span></div>' ;

				echo $input;

			}
			echo '</div>';
		}
		echo '</div>';
	}

	public function inputHtml( $name ,$fail_or_success = true ) {

		$input = '<input type="text" name="' . $name .'" id="' . $name .'"';

		if($fail_or_success == false) $input .= $this->remainValue($name);

		$input .= '>';
		echo $input;

	}

	/**
	 * @param array $array
	 */
	public function countArray( array $array ) {

		$array_count = count( $array );

		return $array_count;
	}

	/**
	 * @param array $first_array
	 * @param array $second_array
	 */
	public function compareArray( array $first_array, array $second_array, $rand = false ) {

		$count_first  = $this->countArray( $first_array );
		$count_second = $this->countArray( $second_array );
		$diff         = absint( $count_second - $count_first );

		if ( $count_first > $count_second ) {

			for ( $i = 0; $i < $diff; $i ++ ) {

				$equal_second_array[] = $second_array[ array_rand( $second_array, 1 ) ];
			}
			$equal_second_array = array_merge( $second_array, $equal_second_array );

		} elseif ( $count_first < $count_second ) {

			$equal_second_array = array_slice( $second_array, 0, $count_first );

		} else {

			$equal_second_array = $second_array;
		}
		if ( $rand ) {
			shuffle( $equal_second_array );
		}

		return $equal_second_array;
	}


	/**
	 * produce first array as key and second array as value
	 * @param array $first
	 * @param array $second
	 *
	 * @return array
	 */
	public function combineArray( array $first, array $second ) {

		$combine =  array_combine( $first, $second );

		return array_map("esc_attr",$combine);

	}

	public function flashMessage( $key = null, $message = null) {
		if ( isset( $key, $message ) ) {

			$_SESSION['seo-alt'][ $key ] = $message;

		}elseif (! isset($key,$message) and isset($_SESSION['seo-alt']))
		{

			echo '<ul class="flash-messages-container">';
			foreach($_SESSION['seo-alt'] as $type => $flash)
			{
				$class = self::flashPregMatch($type);

				echo '<li class="'.$class.'-flash-message">'.$flash.'</li>';
			}
			echo '</ul>';

			unset( $_SESSION['seo-alt']);

		} elseif ( $_SESSION['seo-alt'][ $key ] ) {
			echo '<p class="flash_message">' . $_SESSION['seo-alt'][ $key ] . '</p>';
			unset( $_SESSION['seo-alt'][ $key ] );
		}

		return $this;

	}


	/**
	 * @param $checkbox
	 *
	 * @param $text_input
	 *
	 * @return array
	 */
	public function inputCheckboxToArray( $checkbox, $text_input ) {

		if ( isset( $_POST[ $checkbox ] ) ) {
			$new_alt = $_POST[ $checkbox ];
		}

		if ( ! empty( $_POST[ $text_input ] ) ) {

			$custom_alt = trim( $_POST[ $text_input ] );

			$custom_alt_to_array = explode( ',', $custom_alt );
		}


		if ( ! isset( $new_alt ) and ! isset( $custom_alt_to_array ) ) {

			$this->flashMessage( 'no-alt', __('please select tag or write something!',NABTXD) );
			return false;
		} elseif ( isset( $new_alt ) and isset( $custom_alt_to_array ) ) {
			$final_alts = array_merge( $new_alt, $custom_alt_to_array );

			return $final_alts;

		} elseif ( isset( $new_alt ) ) {
			$final_alts = $new_alt;

			return $final_alts;
		} else {
			$final_alts = $custom_alt_to_array;
		}
		{
			return $final_alts;
		}
	}

	public function redirectBack() {

		header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		exit;
	}

	public function arrayToString( array $array ) {

		return implode( ' ', $array );
	}

	public function remainValue( $from_post,$value = null ) {
		if(isset($_POST[$from_post])){

			$recive = $_POST[ $from_post ];

			if(is_array( $recive ) and in_array($value,$recive))
			{
				return ' checked';
			}
			elseif(is_string($recive)){
				return ' value="'.$recive .'"';
			}

		}
	}

	public function buttonHtml( $name,$id,$js = null ) {

		$button =  '<button type="button" id="'.$id.'" ';
		if($js) $button .= 'onclick="addTag(); return false"';
		$button .= '>'.$name.'</button>';

		echo $button;
	}

	protected function flashPregMatch($str){
		$type = preg_match('/(error|success)/i',$str,$matches);
		return $matches[1];
	}

}
