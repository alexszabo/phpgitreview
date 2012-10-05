phpgitreview
============

a simple PHP based code review tool for git repositories

How to use
-----------

Let's assume you have a local apach up and running in your local directory `/htdocs/`.  
The project you would like to review is in `/htdocs/myproject/`

Copy or clone the **phpgitreview** source code to your local webserver to `/htdocs/phpgitreview/`.
Adjust the `config.php` for example:

    /* The path separator should fit your system. "\\" is for windows machines */
    $repository = array(
	    'location'    => "..\\myproject",   /* will point to /htdocs/myproject */
	    'reviewspath' => "\\reviews",       /* will point to /htdocs/myproject/reviews */
    );

Now open your browser http://localhost/phpgitreview and create or view reviews in your project.
Review files sould have the extension `.review` and are simple text files with the following format:

    