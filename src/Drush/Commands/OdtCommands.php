<?php

namespace Drupal\odt\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
final class OdtCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs an OdtCommands object.
   */
  public function __construct(
    private readonly Token $token,
  ) {
    parent::__construct();
  }
 /**
   * Command description here.
   */
  #[CLI\Command(name: 'oyster_development_tools:paragraph', aliases: ['odt-paragraph'])]
  public function paragraph()
  {
    $pt_name = $this->io()->ask(dt('Paragraph name (32 characters max).'));
    $pt_machine_name = $this->getMachineName($pt_name);
    $pt_machine_name = substr($pt_machine_name, 0, 32);
    $pt_template_name = str_replace('_', '-', $pt_machine_name);

    $pt_description = $this->io()->ask(dt('Paragraph description'));

    // Create the new paragraph type.
    // Define a new Paragraphs Type.
    $paragraph_type = ParagraphsType::create([
      'id' => $pt_machine_name,
      'label' => $pt_name,
      'description' => $pt_description,
    ]);

    // Save the Paragraphs Type.
    $paragraph_type->save();

    // Add fields to the paragraph
    FieldConfig::create([
      'field_name' => 'field_oyster_pt_admin_desc',
      'entity_type' => 'paragraph',
      'bundle' => $pt_machine_name,
      'label' => 'Administration description',
      'required' => FALSE,
      'settings' => [
        'text_processing' => 0, // No HTML filtering.
      ],
    ])->save();

    // Create the form display
    // Step 3: Add a form display configuration for the paragraph type.
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => $pt_machine_name,
      'mode' => 'default',
      'status' => TRUE,
    ]);

    // Add the admin description to the form display.
    $form_display->setComponent('field_oyster_pt_admin_desc', [
      'type' => 'string_textfield',
      'settings' => [
        'size' => 60,
        'placeholder' => 'Enter some text',
      ],
      'weight' => 1,
    ]);

    // Add the published flag to the form display.
    $form_display->setComponent('status', [
      'type' => 'boolean_checkbox',
      'weight' => 0,
    ]);

    // Save the form display configuration.
    $form_display->save();

    $display = new \stdClass();
    $display->group_name = 'settings';
    $display->context = 'form';
    $display->entity_type = 'paragraph';
    $display->bundle = $pt_machine_name;
    $display->mode = 'default';
    $display->label = 'Settings';
    $display->region = 'content';
    $display->parent_name = '';
    $display->weight = '100';
    $display->children = [
      'status',
      'field_oyster_pt_admin_desc',
    ];
    $display->format_type = 'tab';

    $display->format_settings = [
      'classes' => '',
      'show_empty_fields' => false,
      'id' => '',
      'formatter' => 'closed',
      'description' => '',
      'required_fields' => true,
    ];
    field_group_group_save($display);

    $display = new \stdClass();
    $display->group_name = 'content';
    $display->context = 'form';
    $display->entity_type = 'paragraph';
    $display->bundle = $pt_machine_name;
    $display->mode = 'default';
    $display->label = 'Content';
    $display->region = 'content';
    $display->parent_name = '';
    $display->weight = '1';
    $display->children = [
    ];
    $display->format_type = 'tab';

    $display->format_settings = [
      'classes' => '',
      'show_empty_fields' => false,
      'id' => '',
      'formatter' => 'closed',
      'description' => '',
      'required_fields' => true,
    ];
    field_group_group_save($display);

    $display = new \stdClass();
    $display->group_name = 'tabs';
    $display->context = 'form';
    $display->entity_type = 'paragraph';
    $display->bundle = $pt_machine_name;
    $display->mode = 'default';
    $display->label = 'Tabs';
    $display->region = 'content';
    $display->parent_name = '';
    $display->weight = '2';
    $display->children = [
      'content',
      'settings',
    ];
    $display->format_type = 'tabs';

    $display->format_settings = [
      'classes' => '',
      'direction' => 'horizontal',
      'show_empty_fields' => false,
      'id' => '',
      'formatter' => 'closed',
      'description' => '',
      'required_fields' => true,
    ];
    field_group_group_save($display);

    $this->io()->info('Paragraph type created.');

    // Create a template file in the scaffold theme.
    $new_template_file = '/app/web/themes/custom/scaffold/templates/paragraphs/paragraph--' . $pt_template_name . '.html.twig';
    copy('/app/web/themes/custom/scaffold/templates/paragraphs/paragraph.html.twig', $new_template_file);

    // Update the template to include the library
    $this->update_template_file($new_template_file, "{{ attach_library('scaffold/paragraph.') }}", "{{ attach_library('scaffold/paragraph." . $pt_machine_name . "') }}");

    // Update the libraries file in the scaffold theme.
    $libray_path = '/app/web/themes/custom/scaffold/scaffold.libraries.yml';
    $existing = Yaml::parse(file_get_contents($libray_path));
    try {
      $new_library  = ['paragraph.'.$pt_machine_name => [
        'version' => '1.x',
        'css' => [
          'theme' => [
            'css/paragraphs/' . $pt_machine_name . '.css' => []
          ]
        ]
      ]];

      $data = array_merge($existing, $new_library);

      // Convert the array to a YAML string
      $yaml = Yaml::dump($data, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

      // Write the YAML string to a file
      file_put_contents($libray_path, $yaml);

      $this->io()->info('Paragraph library created.');
    } catch (\Symfony\Component\Yaml\Exception\ParseException $exception) {
      $this->io()->error('Unable to open libraries file.');
    }

    // Create the base scss file
    $sass_path = '/app/web/themes/custom/scaffold/scss/paragraphs/';
    $sass_path = $sass_path . $pt_machine_name . '.scss';
$sass_string = '
@import "../base-imports";
.pt--'.str_replace('_','-',$pt_machine_name).' {

  }
}
';
 file_put_contents($sass_path, $sass_string);

  }

}
