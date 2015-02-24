<?php
return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'params' => array(
                    'host'     => 'localhost',
                    'user'     => 'root',
                    'password' => 'root',
                    'dbname'   => 'aks'
                )
            )
        ),
        'configuration' => array(
            'orm_default' => array(
                'metadata_cache'    => 'my_memcache',
                'query_cache'       => 'my_memcache',
                'result_cache'      => 'my_memcache',
            )
        )
    ),
);
