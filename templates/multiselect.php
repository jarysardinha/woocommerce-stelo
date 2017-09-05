<p class="form-row <?php echo implode( ' ', $args['class'] ); ?>" id="<?php echo $key; ?>_field">
	<label for="<?php echo $key; ?>" class="<?php echo implode( ' ', $args['label_class'] ); ?>"><?php echo  $args['label'] . $required; ?></label>
	<select name="<?php echo $key; ?>" id="<?php echo $key; ?>" class="select" multiple="multiple">
	   <?php foreach ( $args['options'] as $option_key => $option_text ) : ?>
	   <option value="<?php echo $option_key ?>" <?php selected( $value, $option_key, true ); ?>><?php echo $option_text; ?></option>';
	   <?php endforeach; ?>
	</select>
</p>';