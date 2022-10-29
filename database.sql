DROP SCHEMA IF EXISTS lbaw2294 CASCADE;
CREATE SCHEMA lbaw2294 AUTHORIZATION postgres;
SET search_path TO lbaw2294;

DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS posts CASCADE;
DROP TABLE IF EXISTS reports CASCADE;
DROP TABLE IF EXISTS stars CASCADE;
DROP TABLE if EXISTS comments CASCADE;
DROP TABLE if EXISTS tags CASCADE;
DROP TABLE if EXISTS user_tags CASCADE;
DROP TABLE if EXISTS question_tags CASCADE;
DROP TABLE if EXISTS badges CASCADE;
DROP TABLE if EXISTS user_badges CASCADE;
DROP TABLE if EXISTS notifications CASCADE;
DROP TABLE if EXISTS new_answers CASCADE;
DROP TABLE if EXISTS new_questions CASCADE;
DROP TABLE if EXISTS new_comments CASCADE;
DROP TABLE if EXISTS new_badges CASCADE;
DROP TABLE if EXISTS new_stars CASCADE;

DROP TYPE if EXISTS post_type CASCADE;
DROP TYPE if EXISTS user_role CASCADE;
DROP TYPE if EXISTS report_type CASCADE;

CREATE TYPE post_type AS ENUM ('question', 'answer');
CREATE TYPE user_role AS ENUM ('Moderator', 'Administrator');
CREATE TYPE report_type AS ENUM ('post', 'comment');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    "name" VARCHAR,
    email VARCHAR UNIQUE NOT NULL,
    username VARCHAR UNIQUE NOT NULL,
    "password" VARCHAR NOT NULL,
    birthday DATE NOT NULL,
    isDeleted BOOLEAN NOT NULL
);

CREATE TABLE roles (
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    userRole user_role NOT NULL
);

CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    postDate DATE NOT NULL,
    postType post_type NOT NULL,
    title VARCHAR NOT NULL CHECK (postType = 'question'),
    postText VARCHAR NOT NULL,
    parentPost INTEGER REFERENCES "posts" (id) ON UPDATE CASCADE,
    isCorrect BOOLEAN NOT NULL CHECK (postType = 'answer')
);

CREATE TABLE stars (
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE,
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE
);

CREATE TABLE comments (
    id SERIAL PRIMARY KEY,
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE,
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    commentText VARCHAR NOT NULL,
    commentDate DATE NOT NULL
);

CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    reportType report_type NOT NULL,
    userID INTEGER REFERENCES "users" (id) ON UPDATE CASCADE,
    postID INTEGER REFERENCES "posts" (id) ON UPDATE CASCADE,
    commentID INTEGER REFERENCES "comments" (id) ON UPDATE CASCADE
);

CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    tagName VARCHAR UNIQUE NOT NULL
);

CREATE TABLE user_tags (
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    tagID INTEGER NOT NULL REFERENCES "tags" (id) ON UPDATE CASCADE
);

CREATE TABLE question_tags (
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE,
    tagID INTEGER NOT NULL REFERENCES "tags" (id) ON UPDATE CASCADE
);

CREATE TABLE badges (
    id SERIAL PRIMARY KEY,
    badgeName VARCHAR UNIQUE NOT NULL
);

CREATE TABLE user_badges (
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    badgeID INTEGER NOT NULL REFERENCES "badges" (id) ON UPDATE CASCADE
);

-- text will be defined in laravel code
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    userID INTEGER NOT NULL REFERENCES "users" (id) ON UPDATE CASCADE,
    isRead BOOLEAN NOT NULL,
    notificationDate DATE NOT NULL
);

CREATE TABLE new_answers (
    notificationID INTEGER NOT NULL REFERENCES "notifications" (id) ON UPDATE CASCADE,
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE,
    questionID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE
);

CREATE TABLE new_questions (
    notificationID INTEGER NOT NULL REFERENCES "notifications" (id) ON UPDATE CASCADE,
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE
);

CREATE TABLE new_comments (
    notificationID INTEGER NOT NULL REFERENCES "notifications" (id) ON UPDATE CASCADE,
    commentID INTEGER NOT NULL REFERENCES "comments" (id) ON UPDATE CASCADE
);

CREATE TABLE new_badges (
    notificationID INTEGER NOT NULL REFERENCES "notifications" (id) ON UPDATE CASCADE,
    badgeID INTEGER NOT NULL REFERENCES "badges" (id) ON UPDATE CASCADE
);

CREATE TABLE new_stars (
    notificationID INTEGER NOT NULL REFERENCES "notifications" (id) ON UPDATE CASCADE,
    postID INTEGER NOT NULL REFERENCES "posts" (id) ON UPDATE CASCADE
);

-- TRIGGERS

DROP FUNCTION IF EXISTS add_answer_notification CASCADE;
DROP FUNCTION IF EXISTS add_question_notification CASCADE;
DROP FUNCTION IF EXISTS add_comment_notification CASCADE;
DROP FUNCTION IF EXISTS add_star_notification CASCADE;

-- ANSWER NOTIFICATIONS
CREATE FUNCTION add_answer_notification() RETURNS TRIGGER AS
$BODY$
BEGIN
    IF NEW.postType = 'answer' THEN
        WITH parent_post AS (
            SELECT parentPost FROM posts WHERE posts.id = NEW.id
        ),
        notified_user AS (
            SELECT userID FROM posts WHERE posts.id = parent_post.id
        ),
        inserted AS (
            INSERT INTO notifications (userID, isRead, notificationDate)
            VALUES (notified_user.id, FALSE, CURRENT_TIMESTAMP)
            RETURNING id
        )
        INSERT INTO new_answers SELECT inserted.id, NEW.id, NEW.parentPost FROM inserted;
    END IF;
    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

CREATE TRIGGER answer_trigger
    BEFORE INSERT OR UPDATE ON posts
    FOR EACH ROW
    EXECUTE PROCEDURE add_answer_notification();

-- QUESTION TAG NOTIFICATIONS
CREATE OR REPLACE FUNCTION add_question_notification() RETURNS TRIGGER AS
$BODY$
BEGIN
    WITH notified_users AS (
        SELECT userID FROM user_tags
        WHERE NEW.tagID = user_tags.tagID
    ),
    inserted AS (
        INSERT INTO notifications (userID, isRead, notificationDate)
        VALUES (notified_users.id, FALSE, CURRENT_TIMESTAMP)
        RETURNING id
    )
    INSERT INTO new_questions SELECT inserted.id, NEW.id, NEW.tagID FROM inserted;
    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

CREATE TRIGGER add_question_notification
    BEFORE INSERT OR UPDATE ON question_tags
    FOR EACH ROW
    EXECUTE PROCEDURE add_question_notification();

-- COMMENT NOTIFICATIONS
CREATE OR REPLACE FUNCTION add_comment_notification() RETURNS TRIGGER AS
$BODY$
BEGIN
    WITH inserted AS (
        INSERT INTO notifications (userID, isRead, notificationDate)
        VALUES (NEW.userID, FALSE, CURRENT_TIMESTAMP)
        RETURNING id
    )
    INSERT INTO new_comments SELECT inserted.id, NEW.badgeID FROM inserted;
    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

CREATE TRIGGER add_comment_notification
    BEFORE INSERT OR UPDATE ON comments
    FOR EACH ROW
    EXECUTE PROCEDURE add_comment_notification();

-- STAR NOTIFICATIONS
CREATE OR REPLACE FUNCTION add_star_notification() RETURNS TRIGGER AS
$BODY$
BEGIN
    WITH notified_user AS (
        SELECT userID FROM posts
        WHERE NEW.postID = posts.id
    ),
    inserted AS (
        INSERT INTO notifications (userID, isRead, notificationDate)
        VALUES (notified_user.id, FALSE, CURRENT_TIMESTAMP)
        RETURNING id
    )
    INSERT INTO new_stars SELECT inserted.id, NEW.postID FROM inserted;
    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

CREATE TRIGGER add_star_notification
    BEFORE INSERT OR UPDATE ON stars
    FOR EACH ROW
    EXECUTE PROCEDURE add_star_notification();

-- PERFORMANCE INDEXES

-- TAGS
CREATE INDEX search_tags ON tags USING hash(tagName);

-- FTS INDEXES

DROP INDEX IF EXISTS search_user;
DROP INDEX IF EXISTS search_post;
DROP INDEX IF EXISTS search_comment;

-- USER SEARCH
ALTER TABLE users
ADD COLUMN tsvectors TSVECTOR;

CREATE FUNCTION user_search_update() RETURNS TRIGGER AS $$
BEGIN
    NEW.tsvectors = (
        (setweight(to_tsvector('english', NEW.name), 'A')) || 
        (setweight(to_tsvector('english', NEW.username), 'B'))
    );
    RETURN NEW;
END $$
LANGUAGE plpgsql;

CREATE TRIGGER user_search_update
    BEFORE INSERT OR UPDATE ON users
    FOR EACH ROW
    EXECUTE PROCEDURE user_search_update();

CREATE INDEX search_user ON users USING GIN (tsvectors);

-- POST SEARCH
ALTER TABLE posts
ADD COLUMN tsvectors TSVECTOR;

CREATE FUNCTION post_search_update() RETURNS TRIGGER AS $$
DECLARE usernameAux VARCHAR;
BEGIN
    SELECT username INTO usernameAux FROM users
    WHERE NEW.userID = users.id;

    IF (NEW.postType = 'question') THEN        
        NEW.tsvectors = (
            (setweight(to_tsvector('english', NEW.title), 'A')) ||
            (setweight(to_tsvector('english', NEW.postText), 'A')) ||
            (setweight(to_tsvector('english', usernameAux), 'B'))
        );
    END IF;
    IF (NEW.postType = 'answer') THEN        
        NEW.tsvectors = (
            (setweight(to_tsvector('english', NEW.postText), 'A')) ||
            (setweight(to_tsvector('english', usernameAux), 'B'))
        );
    END IF;
    RETURN NEW;
END $$
LANGUAGE plpgsql;

CREATE TRIGGER post_search_update
    BEFORE INSERT OR UPDATE ON posts
    FOR EACH ROW
    EXECUTE PROCEDURE post_search_update();

CREATE INDEX search_post ON posts USING GIN (tsvectors);

-- COMMENT SEARCH
ALTER TABLE comments
ADD COLUMN tsvectors TSVECTOR;

CREATE FUNCTION comment_search_update() RETURNS TRIGGER AS $$
BEGIN       
    NEW.tsvectors = ((setweight(to_tsvector('english', NEW.commentText), 'A')));
    RETURN NEW;
END $$
LANGUAGE plpgsql;

CREATE TRIGGER comment_search_update
    BEFORE INSERT OR UPDATE ON comments
    FOR EACH ROW
    EXECUTE PROCEDURE comment_search_update();

CREATE INDEX search_comment ON comments USING GIN (tsvectors);

--TRANSACTIONS
DROP FUNCTION IF EXISTS show_own_questions(INTEGER) CASCADE;
DROP FUNCTION IF EXISTS show_own_answers(INTEGER) CASCADE;
DROP FUNCTION IF EXISTS show_own_comments(INTEGER) CASCADE;
DROP FUNCTION IF EXISTS show_badges(INTEGER) CASCADE;

-- see my own questions
CREATE OR REPLACE FUNCTION show_own_questions(ui INTEGER) RETURNS INTEGER AS $$
    BEGIN
        SET TRANSACTION ISOLATION LEVEL SERIALIZABLE READ ONLY;
        SELECT posts.id, posts.id, posts.postDate, posts.title, posts.postText, COUNT(stars)
        FROM posts
        INNER JOIN users ON posts.userID = users.id
        INNER JOIN stars ON posts.id = stars.postID
        WHERE posts.postType = 'question' AND users.id = ui
        ORDER BY posts.postDate;
    END $$
LANGUAGE plpgsql;

-- see my own answers
CREATE OR REPLACE FUNCTION show_own_answers(ui INTEGER) RETURNS INTEGER AS $$
    BEGIN
        SET TRANSACTION ISOLATION LEVEL SERIALIZABLE READ ONLY;
        SELECT posts.id, posts.postDate, post.parentPost, posts.postText, COUNT(stars), posts.isCorrect
        FROM posts
        INNER JOIN users ON posts.userID = users.id
        INNER JOIN stars ON posts.id = stars.postID
        WHERE posts.postType = 'answer' AND users.id = ui
        ORDER BY posts.postDate;
    END $$
LANGUAGE plpgsql;

-- see my own comments
CREATE OR REPLACE FUNCTION show_own_comments(ui INTEGER) RETURNS INTEGER AS $$
    BEGIN
        SET TRANSACTION ISOLATION LEVEL SERIALIZABLE READ ONLY;
        SELECT comments.id, comments.commentDate, comments.commentText, comments.postID
        FROM comments
        INNER JOIN users ON comments.userID = users.id
        WHERE users.id = ui
        ORDER BY comments.commentDate;
    END $$
LANGUAGE plpgsql;

-- see my own bagdes
CREATE OR REPLACE FUNCTION show_badges(ui INTEGER) RETURNS INTEGER AS $$
    BEGIN
        SET TRANSACTION ISOLATION LEVEL SERIALIZABLE READ ONLY;
        SELECT badges.*
        FROM badges
        INNER JOIN user_badges ON user_badges.badgeID = badge.id
        WHERE user_badges.userID = ui
        ORDER BY badges.id;
    END $$
LANGUAGE plpgsql;




-- INSERT INTO users (name, email, username, password, birthday, isDeleted) VALUES ('Margarida', 'mnps@example.com', 'mnps', 'lalala', TO_DATE('24/10/2001', 'DD/MM/YYYY'), FALSE);
-- INSERT INTO posts (userID, postDate, postType, title, postText, isCorrect) VALUES (1, TO_DATE('24/10/2022', 'DD/MM/YYYY'), 'question', 'birthday', 'is it my birthday?', FALSE);
-- INSERT INTO posts (userID, postDate, postType, postText, parentPost, isCorrect) VALUES (1, TO_DATE('24/10/2022', 'DD/MM/YYYY'), 'answer', 'yeps', 1, FALSE);