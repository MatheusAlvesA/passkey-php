CREATE TABLE users (
	id int NOT NULL AUTO_INCREMENT,
	username VARCHAR(255) NOT NULL,
    password TEXT NOT NULL,
    color VARCHAR(30) NOT NULL,
    
    PRIMARY KEY(id)
); 

CREATE UNIQUE INDEX name_user ON users (username);
