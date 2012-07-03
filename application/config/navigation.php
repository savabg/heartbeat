<?php

return array(
    'top'        => Navbar::create('HeartBeat', Config::get('application.url').'/index.php/projects',
            array(
                array(
                    'attributes' => array(),
                    'items' => array(
                        array('label'=>'Home', 'url' => '#', 'active' => true),
                        array('label'=>'Projects', 'url'=>'#'),
                        array('label'=>'Requirements', 'url'=>'#'),
                        array('label'=>'Tests', 'url'=>'#'),
                        array('label'=>'Adminstrator', 'url'=>'#',
                            'items'=>array(
                                array('label'=>'Action', 'url'=>'#'),
                                array('label'=>'Another action', 'url'=>'#'),
                                array('label'=>'Something else here', 'url'=>'#'),
                                '---',
                                array('header'=>'Nav header'),
                                array('label'=>'Separated link', 'url'=>'#'),
                                array('label'=>'One more separated link', 'url'=>'#'),
                            )
                        )
                    )
                ),
                '<form class="navbar-search pull-left" action="">
        <input type="text" class="search-query span2" placeholder="Search">
    </form>',
                array(
                    'attributes' => array('class' => 'pull-right'),
                    'items' => array(
                        array('label'=>'Link', 'url'=>'#'),
                        '|||',
                        array('label'=>'Dropdown', 'url'=>'#',
                            'items'=>array(
                                array('label'=>'Action', 'url'=>'#'),
                                array('label'=>'Another action', 'url'=>'#'),
                                array('label'=>'Something else here', 'url'=>'#'),
                                '---',
                                array('label'=>'Separated link', 'url'=>'#'),
                            )
                        )
                    )
                ),
            )
        ),
    Navbar::FIX_TOP
);
