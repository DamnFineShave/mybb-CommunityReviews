<?php

$plugins->add_hook('admin_load', ['CommunityReviews', 'admin_load']);
$plugins->add_hook('admin_config_action_handler', ['CommunityReviews', 'admin_config_action_handler']);
$plugins->add_hook('admin_config_menu', ['CommunityReviews', 'admin_config_menu']);
$plugins->add_hook('admin_config_plugins_begin', ['CommunityReviews', 'admin_config_plugins_begin']);
$plugins->add_hook('admin_config_settings_change', ['CommunityReviews', 'admin_config_settings_change']);
$plugins->add_hook('admin_user_users_merge_commit', ['CommunityReviews', 'admin_user_users_merge_commit']);
$plugins->add_hook('admin_user_users_delete_commit_end', ['CommunityReviews', 'admin_user_users_delete_commit_end']);

trait CommunityReviewsHooksACP
{
    static function admin_config_action_handler(&$actions)
    {
        $actions['reviews'] = [
            'active' => 'reviews',
            'file'   => 'reviews',
        ];
    }

    static function admin_config_menu(&$sub_menu)
    {
        global $lang;

        $lang->load('community_reviews');

        $sub_menu[] = [
            'id'    => 'reviews',
            'title' => $lang->community_reviews_admin,
            'link' => 'index.php?module=config-reviews',
        ];
    }

    static function admin_load()
    {
        global $mybb, $cache, $db, $lang, $run_module, $action_file, $page, $sub_tabs;

        if ($run_module == 'config' && $action_file == 'reviews') {
            $lang->load('community_reviews');

            $page->add_breadcrumb_item($lang->community_reviews_admin, 'index.php?module=config-reviews');

            $sub_tabs['categories'] = [
                'link'        => 'index.php?module=config-reviews&action=categories',
                'title'       => $lang->community_reviews_admin_tab_categories,
                'description' => $lang->community_reviews_admin_tab_categories_description,
            ];
            $sub_tabs['fields'] = [
                'link'        => 'index.php?module=config-reviews&action=fields',
                'title'       => $lang->community_reviews_admin_tab_fields,
                'description' => $lang->community_reviews_admin_tab_fields_description,
            ];
            $sub_tabs['merge_products'] = [
                'link'        => 'index.php?module=config-reviews&action=merge_products',
                'title'       => $lang->community_reviews_admin_tab_merge_products,
                'description' => $lang->community_reviews_admin_tab_merge_products_description,
            ];

            if ($mybb->input['action'] == 'categories' || empty($mybb->input['action'])) {

                if ($mybb->get_input('delete') && self::getCategory($mybb->get_input('delete', MyBB::INPUT_INT))) {
                    // action: delete
                    if($mybb->request_method == "post") {
                        if ($mybb->get_input('no')) {
                            admin_redirect('index.php?module=config-reviews&action=categories');
                        } else {
                            self::deleteCategory($mybb->get_input('delete'));
                            flash_message($lang->community_reviews_admin_category_deleted, 'success');
                            admin_redirect('index.php?module=config-reviews&action=categories');
                        }
                	} else {
                		$page->output_confirm_action('index.php?module=config-reviews&action=categories&delete=' . $mybb->get_input('delete', MyBB::INPUT_INT), $lang->community_reviews_admin_category_delete_confirm);
                	}
                } elseif ($mybb->get_input('id')) {
                    // action: update
                    $page->output_header($lang->community_reviews_admin);
                    $page->output_nav_tabs($sub_tabs, 'categories');

                    if ($mybb->request_method == "post") {
                        self::updateCategory($mybb->get_input('id'), [
                            'name' => $db->escape_string($mybb->get_input('name')),
                        ]);
                		flash_message($lang->community_reviews_admin_category_updated, 'success');
                        admin_redirect('index.php?module=config-reviews&action=categories');
                    } else {
                        $item = self::getCategory($mybb->get_input('id', MyBB::INPUT_INT));

                        if ($item) {
                            $form = new Form('index.php?module=config-reviews&action=categories&id=' . $mybb->get_input('id', MyBB::INPUT_INT), 'post');

                            $form_container = new FormContainer($lang->community_reviews_admin_category_edit);
                            $form_container->output_row(
                                $lang->community_reviews_admin_name,
                                '',
                                $form->generate_text_box('name', $item['name'])
                            );
                            $form_container->end();

                            $buttons = null;
                            $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                            $form->output_submit_wrapper($buttons);
                            $form->end();
                        } else {
                            admin_redirect('index.php?module=config-reviews&action=categories');
                        }
                    }
                } else {
                    if ($mybb->request_method == 'post' && $mybb->get_input('add')) {
                        // action: add
                        self::addCategory([
                            'name' => $mybb->get_input('name'),
                        ]);
                        flash_message($lang->community_reviews_admin_category_added, 'success');
                        admin_redirect('index.php?module=config-reviews&action=categories');
                    }

                    // list
                    $page->output_header($lang->community_reviews_admin);
                    $page->output_nav_tabs($sub_tabs, 'categories');

                    $itemsNum = self::countCategories();

                    if ($itemsNum > 0) {
                        $listManager = new CommunityReviews\ListManager([
                            'mybb'          => $mybb,
                            'baseurl'       => 'index.php?module=' . $mybb->get_input('module') . '&action=' . $mybb->get_input('action'),
                            'order_columns' => ['name', 'id'],
                            'items_num'     => $itemsNum,
                            'per_page'      => 20,
                        ]);

                        $items = self::getCategories(null, $listManager->queryOptions());

                        $table = new Table;
                        $table->construct_header($listManager->link('id', $lang->community_reviews_admin_id), ['width' => '5%', 'class' =>  'align_center']);
                        $table->construct_header($listManager->link('name', $lang->community_reviews_admin_name), ['width' => '90%', 'class' => 'align_center']);
                        $table->construct_header($lang->community_reviews_admin_controls, ['width' => '5%', 'class' => 'align_center']);


                        while ($item = $db->fetch_array($items)) {
                            $table->construct_cell($item['id'], ['class' => 'align_center']);
                            $table->construct_cell(htmlspecialchars_uni($item['name']));

                            $popup = new PopupMenu('controls_' . $item['id'], $lang->community_reviews_admin_controls);
                            $popup->add_item($lang->community_reviews_admin_edit, 'index.php?module=config-reviews&amp;action=categories&amp;id=' . $item['id']);
                            $popup->add_item($lang->community_reviews_admin_delete, 'index.php?module=config-reviews&amp;action=categories&amp;delete=' . $item['id'] . '&my_post_key=' . $mybb->post_code);
                            $table->construct_cell(
                                $popup->fetch(),
                                ['class' => 'align_center']
                            );

                            $table->construct_row();
                        }

                        $table->output(sprintf($lang->community_reviews_admin_category_list, $itemsNum));

                        echo $listManager->pagination();
                    }

                    // add form
                    $form = new Form('index.php?module=config-reviews&action=categories&add=1', 'post');

                    $form_container = new FormContainer($lang->community_reviews_admin_category_add);
                    $form_container->output_row(
                        $lang->community_reviews_admin_name,
                        '',
                        $form->generate_text_box('name')
                    );
                    $form_container->end();

                    $buttons = null;
                    $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                    $form->output_submit_wrapper($buttons);
                    $form->end();
                }

            } elseif ($mybb->input['action'] == 'fields') {

                if ($mybb->get_input('delete') && self::getField($mybb->get_input('delete', MyBB::INPUT_INT))) {
                    // action: delete
                    if($mybb->request_method == "post") {
                        if ($mybb->get_input('no')) {
                            admin_redirect('index.php?module=config-reviews&action=fields');
                        } else {
                            self::deleteField($mybb->get_input('delete', MyBB::INPUT_INT));
                            flash_message($lang->community_reviews_admin_field_deleted, 'success');
                            admin_redirect('index.php?module=config-reviews&action=fields');
                        }
                	} else {
                		$page->output_confirm_action('index.php?module=config-reviews&action=fields&delete=' . $mybb->get_input('delete', MyBB::INPUT_INT), $lang->community_reviews_admin_field_delete_confirm);
                	}
                } elseif ($mybb->get_input('copy')) {
                    // action: update
                    $page->output_header($lang->community_reviews_admin);
                    $page->output_nav_tabs($sub_tabs, 'fields');

                    $item = self::getField($mybb->get_input('copy', MyBB::INPUT_INT));

                    if ($item) {
                        $form = new Form('index.php?module=config-reviews&action=fields&add=' . $mybb->get_input('copy', MyBB::INPUT_INT), 'post');

                        $form_container = new FormContainer($lang->community_reviews_admin_field_copy);
                        $form_container->output_row(
                            $lang->community_reviews_admin_name,
                            '',
                            $form->generate_text_box('name', $item['name'])
                        );
                        $form_container->output_row(
                            $lang->community_reviews_admin_category,
                            '',
                            $form->generate_select_box('category_id[]', array_column(self::categoryArray(true), 'name', 'id'), [], [
                                'multiple' => true,
                            ])
                        );
                        $form_container->end();

                        $buttons = null;
                        $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                        $form->output_submit_wrapper($buttons);
                        $form->end();
                    } else {
                        admin_redirect('index.php?module=config-reviews&action=fields');
                    }
                } elseif ($mybb->get_input('id')) {
                    // action: update
                    $page->output_header($lang->community_reviews_admin);
                    $page->output_nav_tabs($sub_tabs, 'fields');

                    if ($mybb->request_method == "post") {
                        $siblingFields = self::getSiblingFields($mybb->get_input('id', MyBB::INPUT_INT));
                        self::updateFields($siblingFields, [
                            'name' => $db->escape_string($mybb->get_input('name')),
                        ]);
                		flash_message($lang->community_reviews_admin_field_updated, 'success');
                        admin_redirect('index.php?module=config-reviews&action=fields');
                    } else {
                        $item = self::getField($mybb->get_input('id', MyBB::INPUT_INT));

                        if ($item) {
                            $form = new Form('index.php?module=config-reviews&action=fields&id=' . $mybb->get_input('id', MyBB::INPUT_INT), 'post');

                            $form_container = new FormContainer($lang->community_reviews_admin_field_edit);
                            $form_container->output_row(
                                $lang->community_reviews_admin_name,
                                '',
                                $form->generate_text_box('name', $item['name'])
                            );
                            $form_container->end();

                            $buttons = null;
                            $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                            $form->output_submit_wrapper($buttons);
                            $form->end();
                        } else {
                            admin_redirect('index.php?module=config-reviews&action=fields');
                        }
                    }
                } else {
                    if ($mybb->request_method == 'post' && $mybb->get_input('add')) {
                        // action: add
                        foreach ($mybb->get_input('category_id', MyBB::INPUT_ARRAY) as $categoryId) {
                            if (self::getCategory($categoryId)) {
                                self::addField([
                                    'category_id' => $categoryId,
                                    'name' => $mybb->get_input('name'),
                                ]);
                            }
                        }
                        flash_message($lang->community_reviews_admin_field_added, 'success');
                        admin_redirect('index.php?module=config-reviews&action=fields');
                    } elseif ($mybb->request_method == 'post' && $mybb->get_input('field_order', MyBB::INPUT_ARRAY)) {
                        foreach ($mybb->get_input('field_order', MyBB::INPUT_ARRAY) as $fieldId => $fieldOrder) {
                            $siblingFields = self::getSiblingFields($fieldId);
                            self::updateFields($siblingFields, [
                                'order' => (int)$fieldOrder,
                            ]);
                        }
                        flash_message($lang->community_reviews_admin_field_order_updated, 'success');
                    }
                    // list
                    $page->output_header($lang->community_reviews_admin);
                    $page->output_nav_tabs($sub_tabs, 'fields');

                    $itemsNum = self::countDistinctFields();

                    if ($itemsNum > 0) {
                        $listManager = new CommunityReviews\ListManager([
                            'mybb'          => $mybb,
                            'baseurl'       => 'index.php?module=' . $mybb->get_input('module') . '&action=' . $mybb->get_input('action'),
                            'order_columns' => ['order', 'category_name', 'name', 'id'],
                            'order_extend'  => '`order` ASC',
                            'items_num'     => $itemsNum,
                            'per_page'      => 20,
                        ]);

                        $items = self::getDistinctFieldsWithCategories($listManager->sql());

	                    $form = new Form('index.php?module=config-reviews&action=fields', 'post');
                        $formContainer = new FormContainer(sprintf($lang->community_reviews_admin_field_list, $itemsNum));

                        $formContainer->output_row_header($listManager->link('name', $lang->community_reviews_admin_name), ['width' => '40%', 'class' => 'align_center']);
                        $formContainer->output_row_header($listManager->link('name', $lang->community_reviews_admin_categories), ['width' => '40%', 'class' => 'align_center']);
                        $formContainer->output_row_header($lang->community_reviews_admin_order, ['width' => '15%', 'class' => 'align_center']);
                        $formContainer->output_row_header($lang->community_reviews_admin_controls, ['width' => '5%', 'class' => 'align_center']);

                        foreach ($items as $item) {
                            $item['id'] = array_values($item['category_fields'])[0]['id'];

                            $categories = [];

                            foreach ($item['category_fields'] as $categoryId => $data) {
                                $categories[] = '&bull; ' . htmlspecialchars_uni($data['category_name']) . ' <a href="index.php?module=config-reviews&amp;action=fields&amp;delete=' . (int)$data['id'] . '">(' . $lang->community_reviews_admin_delete . ')</a>';
                            }

                            $categories = implode('<br />', $categories);

                            $formContainer->output_cell(htmlspecialchars_uni($item['name']));
                            $formContainer->output_cell($categories);
                            $formContainer->output_cell('<input type="text" name="field_order[' . $item['id'] . ']" value="' . $item['order'] . '" class="text_input align_center" style="width: 80%; font-weight: bold;" onfocus="this.select()" />', ['class' => 'align_center']);

                            $popup = new PopupMenu('controls_' . $item['id'], $lang->community_reviews_admin_controls);
                            $popup->add_item($lang->community_reviews_admin_edit, 'index.php?module=config-reviews&amp;action=fields&amp;id=' . $item['id']);
                            $popup->add_item($lang->community_reviews_admin_field_copy, 'index.php?module=config-reviews&amp;action=fields&amp;copy=' . $item['id']);
                            $formContainer->output_cell(
                                $popup->fetch(),
                                ['class' => 'align_center']
                            );
                            $formContainer->construct_row();
                        }

                        $formContainer->end();
                        $form->output_submit_wrapper([
                            $form->generate_submit_button($lang->community_reviews_admin_update_order)
                        ]);
                        $form->end();

                        echo $listManager->pagination();

                        echo '<br />';
                    }

                    // add form
                    $form = new Form('index.php?module=config-reviews&action=fields&add=1', 'post');

                    $form_container = new FormContainer($lang->community_reviews_admin_field_add);
                    $form_container->output_row(
                        $lang->community_reviews_admin_category,
                        '',
                        $form->generate_select_box('category_id[]', array_column(self::categoryArray(true), 'name', 'id'), [], [
                            'multiple' => true,
                        ])
                    );
                    $form_container->output_row(
                        $lang->community_reviews_admin_name,
                        '',
                        $form->generate_text_box('name')
                    );
                    $form_container->end();

                    $buttons = null;
                    $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                    $form->output_submit_wrapper($buttons);
                    $form->end();
                }

            } elseif ($mybb->input['action'] == 'merge_products') {

                if ($mybb->request_method == 'post') {
                    if (self::mergeProduct($mybb->get_input('source_product', MyBB::INPUT_INT), $mybb->get_input('target_product', MyBB::INPUT_INT))) {
                        flash_message($lang->community_reviews_admin_products_merged, 'success');
                        admin_redirect('index.php?module=config-reviews&action=merge_products');
                    }
                }

                $page->output_header($lang->community_reviews_admin);
                $page->output_nav_tabs($sub_tabs, 'merge_products');

                // form
                $form = new Form('index.php?module=config-reviews&action=merge_products', 'post');

                $form_container = new FormContainer($lang->community_reviews_admin_merge_products);
                $form_container->output_row(
                    $lang->community_reviews_admin_source_product,
                    '',
                    $form->generate_numeric_field('source_product')
                );
                $form_container->output_row(
                    $lang->community_reviews_admin_target_product,
                    '',
                    $form->generate_numeric_field('target_product')
                );
                $form_container->end();

                $buttons = null;
                $buttons[] = $form->generate_submit_button($lang->community_reviews_admin_submit);
                $form->output_submit_wrapper($buttons);
                $form->end();
            }

            $page->output_footer();
        }

    }

    public static function admin_user_users_merge_commit()
    {
        global $db, $source_user, $destination_user;

        $db->update_query(
            'community_reviews_products',
            [
                'user_id' => (int)$destination_user['uid'],
            ],
            'user_id=' . (int)$source_user['uid']
        );
        $db->update_query(
            'community_reviews',
            [
                'user_id' => (int)$destination_user['uid'],
            ],
            'user_id=' . (int)$source_user['uid']
        );
        $db->update_query(
            'community_reviews_merchants',
            [
                'user_id' => (int)$destination_user['uid'],
            ],
            'user_id=' . (int)$source_user['uid']
        );
        $db->update_query(
            'community_reviews_comments',
            [
                'user_id' => (int)$destination_user['uid'],
            ],
            'user_id=' . (int)$source_user['uid']
        );
    }

    public static function admin_user_users_delete_commit_end()
    {
        global $db, $user;

        $db->delete_query('community_reviews', "user_id =" . (int)$destination_user['uid']);
        $db->delete_query('community_reviews_merchants', "user_id =" . (int)$destination_user['uid']);
        $db->delete_query('community_reviews_comments', "user_id =" . (int)$destination_user['uid']);
    }

    public static function admin_config_settings_change()
    {
        global $lang;
        $lang->load('community_reviews');
    }

    static function admin_config_plugins_begin()
    {
        global $mybb, $db, $cache, $lang;

        if (CommunityReviewsMyalertsIntegrable()) {
            CommunityReviewsMyalertsInit();
        } else {
            return false;
        }

        $lang->load('community_reviews');

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if ($mybb->get_input('community_reviews_myalerts_install') && verify_post_check($mybb->get_input('my_post_key'))) {
            if (!$alertTypeManager->getByCode('community_reviews_merchant_tag')) {
                self::installMyalertsIntegration();
                flash_message($lang->community_reviews_myalerts_installed, 'success');
                admin_redirect("index.php?module=config-plugins");
            }
        }

        if ($mybb->get_input('community_reviews_myalerts_uninstall') && verify_post_check($mybb->get_input('my_post_key'))) {
            if ($alertTypeManager->getByCode('community_reviews_merchant_tag')) {
                self::uninstallMyalertsIntegration();
                flash_message($lang->community_reviews_myalerts_uninstalled, 'success');
                admin_redirect("index.php?module=config-plugins");
            }
        }

        $installed = $alertTypeManager->getByCode('community_reviews_merchant_tag');

        $actionUrl = 'index.php?module=config-plugins&amp;community_reviews_myalerts_' . ($installed ? 'uninstall' : 'install') . '=1&amp;my_post_key=' . $mybb->post_code;
        $actionText = $installed ? $lang->community_reviews_myalerts_uninstall : $lang->community_reviews_myalerts_install;
        CommunityReviews::$descriptionAppendix .= $locationName . '<br /> <a href="' . $actionUrl . '">[' . $actionText . ']</a>';
    }
}
