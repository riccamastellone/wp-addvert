<?php if($updated): ?>
<div class="updated"><p><?php _e('Settings updated successfull') ?></p></div>
<?php endif ?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"><br></div>
    <h2><a href="<?php echo $url; ?>" target="_blank">Addvert</a> WP Plugin</h2>
    <p>
        L'ID E-Commerce e la Chiave Segreta sono visibili nella tua pagina e-commerce su Addvert. <br>
        Puoi accedere alla pagina con questo link:
        <a href="<?php echo $url ?>" target="_blank"><?php echo $url ?></a>
    </p>
    <form method="post" action="options.php">
        <?php settings_fields('addvert_plugin_options'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">ID E-Commerce</th>
                <td>
                    <input name="addvert_options[addvert_id]" type='text' value='<?php echo $options['addvert_id'] ?>'/>
                </td>
            </tr>
            <tr>
                <th scope="row">Chiave Segreta</th>
                <td>
                    <input name="addvert_options[addvert_secret]" type='text' value='<?php echo $options['addvert_secret'] ?>'/>
                </td>
            </tr>
            <tr>
                <th scope="row">Dimensione</th>
                <td>
                    <span>Width:</span>
                    <input name="addvert_options[addvert_width]" type="text" value="<?php echo $options['addvert_width'] ?>" style="width:100px;display:inline-block" />px
                    
                    <span style="margin-left:20px">Height:</span>
                    <input name="addvert_options[addvert_height]" type="text" value="<?php echo $options['addvert_height'] ?>" style="width:100px;display:inline-block" />px

                </td>
            </tr>
            <tr>
                <th scope="row">Scegli il layout che vuoi utilizzare</th>
                <td>
                    <select name="addvert_options[addvert_layout]">
                        <?php foreach($layouts as $btn): ?>
                        <option value="<?php echo $btn ?>"<?php if($btn === $this_btn) echo 'selected="selected"' ?>>
                        <?php echo $btn ?>
                        </option>
                        <?php endforeach ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Nascondi contatore?</th>
                <td>
                    <input type="hidden" name="addvert_options[addvert_nocounter]" value="0">
                    <input type="checkbox" name="addvert_options[addvert_nocounter]" value="1">
                </td>
            </tr>
            <tr>
                <th scope="row">Vuoi usare lo shortcode [addvert] per mostrare il button?</th>
                <td>
                    <input type="radio" name="addvert_options[addvert_shortcode]" value="0" <?php if($options['addvert_shortcode']== '0') echo 'checked="checked"';?>/> NO<br>
                    <input type="radio" name="addvert_options[addvert_shortcode]" value="1" <?php if($options['addvert_shortcode']== '1') echo 'checked="checked"';?>/> SI
                </td>
            </tr>
        </table>
        <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
        </p>
    </form>
</div>
