<!-- Improved compatibility of back to top link: See: https://github.com/othneildrew/Best-README-Template/pull/73 -->
<a name="readme-top"></a>
<!--
*** Thanks for checking out the Best-README-Template. If you have a suggestion
*** that would make this better, please fork the repo and create a pull request
*** or simply open an issue with the tag "enhancement".
*** Don't forget to give the project a star!
*** Thanks again! Now go create something AMAZING! :D
-->



<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->



<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/othneildrew/Best-README-Template">
    <img src="./public/img/logo_header.png" alt="Logo" width="400">
  </a>

  <h3 align="center">Form Management System</h3>

  <p align="center">
    <!--Description -->
    <br />
    <br />
    <br />
<!--     <a href="https://popdev.online">View Demo</a> -->
    ·
    <a href="https://github.com/ccmanganti/fms/issues">Report Bug</a>
    ·
    <a href="https://github.com/ccmanganti/fms/issues">Request Feature</a>
  </p>
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#contact">Contact</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

<!-- <img src="./public/img/358646786_1027175638659287_6984588511889434154_n (1).png" alt="Logo" width="700"> -->


Extensive Description here...

<p align="right">(<a href="#readme-top">back to top</a>)</p>



### Built With

At this system's core are these technologies.

- Laravel
- Filament
- MySQL
- PDFtk
- PhpSpreadsheet

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- GETTING STARTED -->
## Getting Started

To set up your this Laravel project locally, follow these simple steps:

### Prerequisites

PHP: Make sure you have PHP installed on your machine. You can download the latest version of PHP from the official PHP website (https://www.php.net/) and follow the installation instructions for your specific operating system.

Composer: Install Composer, a dependency management tool for PHP, on your local machine. You can download Composer from the official website (https://getcomposer.org/) and follow the installation instructions.

Laravel: Ensure you have Laravel installed globally on your system. Open a command-line interface (CLI) and run the following command:

PDFtk: Install PDFtk on your system. Make sure to use its default configuration. It will be use for pdf-related development. (https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/)


### Installation

Follow these steps to install and set up your Laravel project:
Clone the repository:

 ```sh
    git clone https://github.com/ccmanganti/fms.git
   ```

Install dependencies:

 ```sh
    cd project-name
    composer install
   ```

Set up the environment:
```sh
    php artisan key:generate
   ```

Link the storage
```sh
    php artisan link:storage
   ```

Database configuration:

1. Create a .env file and copy the contents of .env.example. 

2. In the .env file, update the database connection settings according to your local environment. Set the database name, username, and password.

Run database migrations:

```sh
    php artisan migrate
   ```

Seed the setup resources:

```sh
    php artisan db:seed
    php artisan db:seed --class=PhilbrgySeeder --class=PhilmuniSeeder --class=PhilprovinceSeeder
   ```

Configure Filament:

1. Copy the example files from the `example_files` folder to your project's directory.

2. Replace the following files in your project with the example files from the `example_files` folder:

- Replace `vendor/filament/filament/config/filament.php` with `example_files/filament.php.example`
- Replace `vendor/filament/filament/resources/views/components/brand.blade.php` with `example_files/brand.blade.php.example`

- Replace `vendor/filament/filament/dist` folder with `example_files/dist.example` folder

```sh
    php artisan db:seed
    php artisan db:seed --class=PhilbrgySeeder --class=PhilmuniSeeder --class=PhilprovinceSeeder
   ```


Serve the application:
```sh
    php artisan serve
   ```
<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- USAGE EXAMPLES -->
## Usage

Usage Description:

Usage desciption here...

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTACT -->
## Contact

Your Name - [@ccmanganti](https://facebook.com/ccmanganti) - cpe.christopherclarkcmanganti@gmail.com

Project Link: [https://github.com/ccmanganti/fms](https://github.com/ccmanganti/fms)

<p align="right">(<a href="#readme-top">back to top</a>)</p>

