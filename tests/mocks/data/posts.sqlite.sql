CREATE TABLE posts (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(32) NOT NULL ,
    body TEXT NOT NULL,
    created DATETIME NULL,
    updated DATETIME NULL
);
