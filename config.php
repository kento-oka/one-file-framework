<?php

function foo(){
    return [
        [
            "id" => 123,
            "username" => "okaken",
        ],
        [
            "id" => 123,
            "username" => "okaken",
        ],
        [
            "id" => 123,
            "username" => "okaken",
        ],
    ];
}

function bar(int $userId){
    return [
        "id" => $userId,
        "username" => "okaken",
    ];
}

return [
    "routes"  => [
        "GET"   => [
            "/" => [
                "action"    => function(){return ["path" => "/"];},
            ],
            "/users" => [
                "action"    => "foo",
            ],
            "/users/:userId" => [
                "action"    => "bar",
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