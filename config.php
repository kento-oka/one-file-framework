<?php
return [
    "routes"  => [
        "GET"   => [
            "/" => [
                "action"    => function(){return ["path" => "/"];},
            ],
            "/users" => [
                "action"    => function(){return ["path" => "/users"];},
            ],
            "/users/:userId" => [
                "action"    => function(int $userId){return ["path" => "/users/:userId", "args" => func_get_args()];},
            ],
            "/users/:userId/books" => [
                "action"    => function(){return ["path" => "/users/:userId/books"];},
            ],
            "/users/:userId/books/:bookId" => [
                "action"    => function(int $bookId, string $userId){return ["path" => "/users/:userId/books/:book_id", "args" => func_get_args()];},
            ],
            "/authors"  => [
                "action"    => function(){return ["path" => "/authors"];},
            ],
            "/authors/:authorId"  => [
                "action"    => function(){return ["path" => "/authors/:authorId"];},
            ],
        ]
    ]
];