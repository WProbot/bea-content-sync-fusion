<?php
class BEA_CSF_Client_Taxonomy {

	/**
	 * Add term on DB
	 */
	public static function merge( array $data, BEA_CSF_Synchronization $sync ) {
		// Test datas validity
		if ( empty($data) || !is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		// Get local ID for parent, from TT_ID
		$data['parent_tt_id'] = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $data['parent_tt_id'] );
		if ( $data['parent_tt_id'] > 0 ) {
			$data['parent'] = (int) get_term_id_from_term_taxonomy_id( $data['taxonomy'], $data['parent_tt_id'] );
		}

		// Term exists ?
		$local_term_id = 0;
		$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $data['term_taxonomy_id'] );
		if ( $local_tt_id > 0 ) {
			$local_term_id = (int) get_term_id_from_term_taxonomy_id( $data['taxonomy'], $local_tt_id );
		}

		if ( $local_term_id == 0 ) {
			//$edit = false;
			$new_term_id = wp_insert_term( $data['name'], $data['taxonomy'], array( 'description' => $data['description'], 'slug' => $data['slug'], 'parent' => $data['parent'] ) );

			// try to manage error when term already exist with the same name !
			if ( is_wp_error( $new_term_id ) && $new_term_id->get_error_code() == 'term_exists' ) {
				$term_exists_result = term_exists( $data['name'], $data['taxonomy'], $data['parent'] );
				if ( $term_exists_result != false ) {
					$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $term_exists_result['term_taxonomy_id'] );
					if ( $local_tt_id == 0 ) { // No master ID? no sync item !
						$new_term_id = $term_exists_result;
						update_term_meta( $data['taxonomy'], $term_exists_result['term_id'], 'already_exists', 1 );
					}
				}
			}
		} else {
			//$edit = true;
			$new_term_id = wp_update_term( $local_term_id, $data['taxonomy'], array( 'name' => $data['name'], 'description' => $data['description'], 'slug' => $data['slug'], 'parent' => $data['parent'] ) );
		}

		// Test merge/insertion
		if ( is_wp_error( $new_term_id ) ) {
			return new WP_Error( 'term_insertion', $new_term_id->get_error_message() );
		} elseif ( is_array( $new_term_id ) && isset( $new_term_id['term_id'] ) ) {
			$new_term_id = (int) $new_term_id['term_id'];
		} elseif ( (int) $new_term_id != 0 ) {
			$new_term_id = (int) $new_term_id;
		}

		// Always valid ?
		if ( $new_term_id == 0 ) {
			return new WP_Error( 'term_id_invalid', 'Error - Term ID is egal to 0' );
		}

		// Save master id
		update_term_meta( $data['taxonomy'], $new_term_id, 'master_id', $data['term_taxonomy_id'] );

		// Get full term datas
		$new_term = get_term( $new_term_id, $data['taxonomy'] );

		return (int) $new_term->term_id;
	}

	/**
	 * Delete a term, take the master id, try to find the new ID and delete local term
	 * 
	 * @param array $data
	 * @return \WP_Error|boolean
	 */
	public static function delete( array $data, BEA_CSF_Synchronization $sync ) {
		// Test datas validity
		if ( empty( $data ) || !is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		// Term exists ?
		$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $data['term_taxonomy_id'] );
		if ( $local_tt_id > 0 ) {
			// Term already exist before sync, keep it !
			$already_exists = (int) get_term_taxonomy_id_from_meta( 'already_exists', (int) $data['term_taxonomy_id'] );
			if ( $already_exists == 1 ) {
				return false;
			}

			$local_term_id = (int) get_term_id_from_term_taxonomy_id( $data['taxonomy'], $local_tt_id );
			if ( $local_term_id > 0 ) {
				wp_delete_term( $local_term_id, $data['taxonomy'] );
			}
		}

		return true;
	}

}