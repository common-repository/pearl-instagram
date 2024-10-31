<?php

if ( ! class_exists( 'Pearl_Instagram_Widget' ) ) {

    class Pearl_Instagram_Widget extends WP_Widget {

        /**
         * Sets up the Pearl Instagram Widget
         */
        public function __construct() {

            $widget_ops = array(
                'classname' => 'pearl_instagram_widget',
                'description' => esc_html__('Display recent Instagram photos.','pearl-instagram'),
            );
            parent::__construct( 'pearl_instagram_widget', esc_html__( 'Instagram by PearlThemes', 'pearl-instagram' ), $widget_ops );
        }

        function widget( $args, $instance ) {

            $username = empty( $instance['username'] ) ? '' : $instance['username'];
            $limit    = empty( $instance['number'] ) ? 9 : $instance['number'];
            $columns  = empty( $instance['columns'] ) ? 3 : $instance['columns'];
            $size     = empty( $instance['size'] ) ? 'large' : $instance['size'];
            $target   = empty( $instance['target'] ) ? '_self' : $instance['target'];
            $link     = empty( $instance['link'] ) ? '' : $instance['link'];

            echo $args['before_widget'];

            if ( ! empty( $instance['title'] ) ) {
                echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
            }

            do_action( 'pearl_before_widget', $instance );

            if ( $username != '' ) {

                $media_array = $this->fetch_instagram( $username, $limit );

                if ( is_wp_error( $media_array ) ) {

                    echo $media_array->get_error_message();

                } else {

                    // filter for the images only
                    if ( $images_only = apply_filters( 'pearl_images_only', false ) ) {
                        $media_array = array_filter( $media_array, array( $this, 'images_only' ) );
                    }

                    ?><div class="<?php echo esc_attr( 'pearl-instagram-pics pearl-instagram-size-' . $size ); ?>">
                        <?php

                        $last_id  = end( $media_array );
                        $last_id  = $last_id['id'];

                        $row_count = 0;
                        foreach ( $media_array as $item ) {

                            if( $row_count == 0 )
                            {
                                echo "<div class='pearl-instagram-row'>";
                            }

                            $row_count ++ ;
                            $targeting = $target;
                            if($target == "lightbox")
                            {
                                $targeting = "";
                                $item['link'] = $item['original'];
                            }

                            echo '<div class="pearl-instagram-item">';
                            echo '<a href="'. esc_url( $item['link'] ) .'" target="'. esc_attr( $targeting ) .'" class="'.$target.'" data-rel="lightcase:instagram">';
                            echo '<img src="'. esc_url( $item[$size] ) .'"  alt="'. esc_attr( $item['description'] ) .'" title="'. esc_attr( $item['description'] ).'"/>';
                            echo '</a></div>';

                            if($row_count % $columns == 0 || $last_id == $item['id'])
                            {
                                echo '</div>';
                                $row_count = 0;
                            }

                        }
                    echo '</div>';
                }
            }

            if ( $link != '' ) {
                ?>
                <a class="pearl-instagram-follow" href="//instagram.com/<?php echo esc_attr( trim( $username ) ); ?>" rel="me" target="<?php echo esc_attr( $target ); ?>"><i class="fa fa-instagram"></i><?php echo esc_html($link); ?></a><?php
            }

            do_action( 'pearl_after_widget', $instance );

            echo $args['after_widget'];
        }

        function fetch_instagram( $username, $slice = 9 ) {

        $username = strtolower( $username );
        $username = str_replace( '@', '', $username );

        if ( false === ( $instagram = get_transient( 'pearl_insta1-'.sanitize_title_with_dashes( $username ) ) ) ) {

            //$remote = wp_remote_get( 'http://instagram.com/'.trim( $username ) );
            $remote = wp_remote_get( 'https://www.instagram.com/'.trim( $username ), array( 'sslverify' => false, 'timeout' => 60 ) );

            if ( is_wp_error( $remote ) )
                {return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'pearl-instagram' ) );}

            if ( 200 != wp_remote_retrieve_response_code( $remote ) )
                {return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'pearl-instagram' ) );}

            $shards = explode( 'window._sharedData = ', $remote['body'] );
            $insta_json = explode( ';</script>', $shards[1] );
            $insta_array = json_decode( $insta_json[0], true );

            if ( ! $insta_array )
                {return new WP_Error( 'bad_json', esc_html__( 'Instagram has returned invalid data.', 'pearl-instagram' ) );}

            if ( isset( $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'] ) ) {
                $images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
            } else {
                return new WP_Error( 'bad_json_2', esc_html__( 'Instagram has returned invalid data.', 'pearl-instagram' ) );
            }

            if ( ! is_array( $images ) )
                {return new WP_Error( 'bad_array', esc_html__( 'Instagram has returned invalid data.', 'pearl-instagram' ) );}

            $instagram = array();

            foreach ( $images as $image ) {

                $image['thumbnail_src'] = preg_replace( "/^https:/i", "", $image['thumbnail_src'] );
                $image['thumbnail'] = str_replace( 's640x640', 's160x160', $image['thumbnail_src'] );
                $image['small'] = str_replace( 's640x640', 's320x320', $image['thumbnail_src'] );
                $image['large'] = $image['thumbnail_src'];
                $image['display_src'] = preg_replace( "/^https:/i", "", $image['display_src'] );

                if ( $image['is_video'] == true ) {
                    $type = 'video';
                } else {
                    $type = 'image';
                }

                $caption = esc_html__( 'Instagram Image', 'pearl-instagram' );
                if ( ! empty( $image['caption'] ) ) {
                    $caption = $image['caption'];
                }

                $instagram[] = array(
                    'description'   => $caption,
                    'link'		  	=> '//instagram.com/p/' . $image['code'],
                    'time'		  	=> $image['date'],
                    'comments'	  	=> $image['comments']['count'],
                    'likes'		 	=> $image['likes']['count'],
                    'thumbnail'	 	=> $image['thumbnail'],
                    'small'			=> $image['small'],
                    'large'			=> $image['large'],
                    'original'		=> $image['display_src'],
                    'type'		  	=> $type,
                    'id'			=> $image['id']
                );
            }

            // do not set an empty transient - should help catch private or empty accounts
            if ( ! empty( $instagram ) ) {

                $instagram = base64_encode( serialize( $instagram ) );
                set_transient( 'pearl_insta1-'.sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS*2 ) );
            }
        }

        if ( ! empty( $instagram ) ) {

            $instagram = unserialize( base64_decode( $instagram ) );
            return array_slice( $instagram, 0, $slice );

        } else {

            return new WP_Error( 'no_images', esc_html__( 'Instagram did not return any images.', 'pearl-instagram' ) );

        }
        }

        function form( $instance ) {

            $instance = wp_parse_args( (array) $instance, array(
                    'title' => esc_html__( 'Instagram', 'pearl-instagram' ),
                    'username' => '',
                    'size' => 'large',
                    'link' => esc_html__( 'Follow Me!', 'pearl-instagram' ),
                    'number' => 9,
                    'target' => 'lightbox' ,
                    'columns' => 3 )
            );

            $title    = esc_attr( $instance['title'] );
            $username = esc_attr( $instance['username'] );
            $number   = absint( $instance['number'] );

            if($number > 12) { $number = 12; }

            $size     = esc_attr( $instance['size'] );
            $target   = esc_attr( $instance['target'] );
            $link     = esc_attr( $instance['link'] );
            $columns  = esc_attr( $instance['columns'] );

        ?>
            <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title', 'pearl-instagram' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_html( $title ); ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php esc_html_e( 'Username', 'pearl-instagram' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo esc_html($username); ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of photos (maximum 12)', 'pearl-instagram' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo absint($number); ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php esc_html_e( 'Number of columns', 'pearl-instagram' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" type="number" value="<?php echo absint($columns); ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php esc_html_e( 'Photo size', 'pearl-instagram' ); ?>:</label>
                <select id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" class="widefat">
                    <option value="thumbnail" <?php selected( 'thumbnail', $size ) ?>><?php esc_html_e( 'Thumbnail', 'pearl-instagram' ); ?></option>
                    <option value="small" <?php selected( 'small', $size ) ?>><?php esc_html_e( 'Small', 'pearl-instagram' ); ?></option>
                    <option value="large" <?php selected( 'large', $size ) ?>><?php esc_html_e( 'Large', 'pearl-instagram' ); ?></option>
                    <option value="original" <?php selected( 'original', $size ) ?>><?php esc_html_e( 'Original', 'pearl-instagram' ); ?></option>
                </select>
            </p>
            <p><label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php esc_html_e( 'Open links in', 'pearl-instagram' ); ?>:</label>
                <select id="<?php echo $this->get_field_id( 'target' ); ?>" name="<?php echo $this->get_field_name( 'target' ); ?>" class="widefat">
                    <option value="lightbox" <?php selected( 'lightbox', $target ) ?>><?php esc_html_e( 'Lightbox', 'pearl-instagram' ); ?></option>
                    <option value="_self" <?php selected( '_self', $target ) ?>><?php esc_html_e( 'Current window (_self)', 'pearl-instagram' ); ?></option>
                    <option value="_blank" <?php selected( '_blank', $target ) ?>><?php esc_html_e( 'New window (_blank)', 'pearl-instagram' ); ?></option>
                </select>
            </p>
            <p><label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php esc_html_e( 'Follow Link Text', 'pearl-instagram' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>" type="text" value="<?php echo esc_html($link); ?>" /></label></p>
        <?php
        }

        // based on https://gist.github.com/cosmocatalano/4544576

        function update( $new_instance, $old_instance ) {

            $instance = $old_instance;
            $instance['title'] = strip_tags( $new_instance['title'] );
            $instance['username'] = trim( strip_tags( $new_instance['username'] ) );
            $instance['number'] = ! absint( $new_instance['number'] ) ? 9 : $new_instance['number'];
            $instance['columns'] = ! absint( $new_instance['columns'] ) ? 3 : $new_instance['columns'];

            if($instance['columns'] > 6) { $instance['columns'] = 6; }
            if($instance['columns'] < 1) { $instance['columns'] = 1; }


            $instance['size'] = ( ( $new_instance['size'] == 'thumbnail' || $new_instance['size'] == 'large' || $new_instance['size'] == 'small' || $new_instance['size'] == 'original' ) ? $new_instance['size'] : 'large' );
            $instance['target'] = ( ( $new_instance['target'] == '_self' || $new_instance['target'] == '_blank'|| $new_instance['target'] == 'lightbox' ) ? $new_instance['target'] : '_self' );
            $instance['link'] = strip_tags( $new_instance['link'] );
            return $instance;
        }

        public function images_only( $media_item ) {

            if ( $media_item['type'] == 'image' )
                {return true;}

            return false;
        }
    }
}

