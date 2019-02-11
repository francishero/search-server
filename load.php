<?php
/**
 * File header placeholder
 */

/*
$links = [];
if (($handle = fopen(__DIR__ . '/ml-20m/links.csv', "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $links[$data[0]] = $data;
    }
    fclose($handle);
}


if (($handle = fopen(__DIR__ . '/ml-20m/movies.csv', "r")) !== FALSE) {
    fgetcsv($handle, 1000, ",");
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $movie = [
            'id' => $data[0],
            'title' => $data[1],
            'categories' => explode('|', $data[2]),
            'imdb' => $links[$data[0]][1],
            'themovidedb' => $links[$data[0]][2],
        ];

        $opts = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => json_encode([
                        'items' => [
                            [
                                'uuid' => [
                                    'type' => 'movie',
                                    'id' => $data[0]
                                ],
                                'metadata' => [
                                    'categories' => explode('|', $data[2]),
                                    'title' => $data[1]
                                ],
                                'searchable_metadata' => [
                                    'title' => $data[1],
                                ],
                                'exact_matching_metadata' => [
                                    $data[0]
                                ]
                            ]
                        ]
                    ])
                )
        );

        $context = stream_context_create($opts);
        file_get_contents("http://localhost:8080/v1/items?app_id=pio&index=movies&token=0e4d75ba-c640-44c1-a745-06ee51db4e93", false, $context);
    }
    fclose($handle);
}
*/
$interactions = [];
$row = 1;
if (($handle = fopen(__DIR__ . '/ml-20m/ratings.csv', "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $opts = array(
            'http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => json_encode([
                        'user' => [
                            'id' => $data[0]
                        ],
                        'item_uuid' => [
                            'id' => $data[1],
                            'type' => 'movie',
                        ],
                        'weight' => (int) $data[2]
                    ])
                )
        );

        $context = stream_context_create($opts);
        file_get_contents("http://localhost:8080/v1/interaction?app_id=pio&token=0e4d75ba-c640-44c1-a745-06ee51db4e93", false, $context);

        if ($row++ > 1000000) {
            break;
        }
    }
    fclose($handle);
}

var_dump(array_slice($interactions, 0, 10));
