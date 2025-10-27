CREATE TABLE users (
    UserID INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    FirstName CHAR(40),
    LastName CHAR(40),
    Username VARCHAR(20) NOT NULL,
    Email VARCHAR(40) NOT NULL,
    UserPassword VARCHAR(40) NOT NULL,
    isAdmin BOOLEAN NOT NULL
);

CREATE TABLE movies (
  MovieCode         VARCHAR(20)  NOT NULL PRIMARY KEY,  
  Title             VARCHAR(100) NOT NULL,              
  PosterPath        VARCHAR(255),                       
  Synopsis          TEXT NOT NULL,
  Genre             VARCHAR(30) NOT NULL,                  
  TicketPrice       FLOAT NOT NULL,              
  Rating            VARCHAR(10)  NOT NULL,              
  ReleaseDate       DATE,
  DurationMinutes   INT NOT NULL,
  Trending          TINYINT(1)   NOT NULL DEFAULT 0,
  OnSale            TINYINT(1)   NOT NULL DEFAULT 0,    
  Language          VARCHAR(30)  NOT NULL DEFAULT 'English'
);

CREATE TABLE IF NOT EXISTS jobs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)  NOT NULL,
  email       VARCHAR(255)  NOT NULL,
  start_date  DATE          NOT NULL,
  birthday    DATE          NOT NULL,
  experience  TEXT          NOT NULL,
  UNIQUE INDEX idx_jobs_email (email),
  INDEX idx_jobs_start_date (start_date)
)

CREATE TABLE screentime (
  hall_code   VARCHAR(4) NOT NULL,          
  timeslot    TIME NOT NULL,                
  movie_code  VARCHAR(32) NOT NULL,          
  PRIMARY KEY (hall_code, timeslot),
  CONSTRAINT fk_movie FOREIGN KEY (movie_code) REFERENCES movies(MovieCode)
);

CREATE TABLE tickets (
  TicketID INT AUTO_INCREMENT PRIMARY KEY,
  OrderID  INT NOT NULL,
  HallID VARCHAR(10) NOT NULL,
  ShowDate DATE NOT NULL,
  TimeSlot TIME NOT NULL,
  SeatCode VARCHAR(10) NOT NULL,
  MovieCode VARCHAR(20),
  UserID INT UNSIGNED,
  BookingTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_seat (HallID, ShowDate, TimeSlot, SeatCode),
  CONSTRAINT fk_ticket_user
    FOREIGN KEY (UserID) REFERENCES users(UserID),
  CONSTRAINT fk_ticket_movie
    FOREIGN KEY (MovieCode) REFERENCES movies(MovieCode),
  CONSTRAINT fk_ticket_order
    FOREIGN KEY (OrderID) REFERENCES bookings(OrderID)
      ON DELETE CASCADE
);


CREATE TABLE bookings (
  OrderID        INT AUTO_INCREMENT PRIMARY KEY,
  CustName       VARCHAR(100) NOT NULL,
  CustEmail      VARCHAR(100) NOT NULL,
  CustPhone      VARCHAR(30)  NOT NULL,
  PaymentMethod  ENUM('cash','card') NOT NULL,
  PaidAmount     DECIMAL(10,2) NOT NULL,  
  UserID         INT UNSIGNED NULL,
  CreatedAt      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_booking_user
    FOREIGN KEY (UserID)
    REFERENCES users(UserID)
    ON DELETE SET NULL
    ON UPDATE CASCADE
);

