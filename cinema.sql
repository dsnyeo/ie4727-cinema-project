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

-- Screenings (showtimes)
CREATE TABLE screenings (
  ScreeningID INT AUTO_INCREMENT PRIMARY KEY,
  MovieCode     INT NOT NULL,
  HallID      INT NOT NULL,
  StartTime   DATETIME NOT NULL,
  FOREIGN KEY (MovieCode) REFERENCES movies(MovieID) ON DELETE CASCADE,
  FOREIGN KEY (HallID)  REFERENCES halls(HallID)  ON DELETE CASCADE
);

CREATE TABLE bookings (
  BookingID    INT AUTO_INCREMENT PRIMARY KEY,
  UserID       INT NOT NULL,
  ScreeningID  INT NOT NULL,
  BookedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TotalAmount  DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (ScreeningID) REFERENCES screenings(ScreeningID) ON DELETE CASCADE
  FOREIGN KEY (UserID) REFERENCES users(UserID)
);

CREATE TABLE booking_seats (
  BookingSeatID INT AUTO_INCREMENT PRIMARY KEY,
  BookingID     INT NOT NULL,
  SeatID        INT NOT NULL,
  PricePaid     DECIMAL(8,2) NOT NULL,
  UNIQUE (BookingID, SeatID),
  FOREIGN KEY (BookingID) REFERENCES bookings(BookingID) ON DELETE CASCADE,
  FOREIGN KEY (SeatID)    REFERENCES seats(SeatID)
);

