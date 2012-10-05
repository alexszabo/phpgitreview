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


Status Levels
---------------

In this order:

* **ok** = reviewd and nothing to grump about
* **info** = nice to know (mind next time maybe)
* **warn** = not wrong or broken, but should be improved
* **error** = broken in some way, fix it



Review file format: anyname.review
---------------

    since: [SHA1 from commit]
    until: [SHA1 to commit]
    
    //--------------------------------
    file: path/to-file/filename.md
    //--------------------------------
    
    //use the word line or lines
    line: 5
    status: info
    comment by John Doe: Some comment here using **markdown**.
    
    //you can use ranges and list multiple lines
    lines: 10-15, 19, 22-27
    status: ok
    
    //removed lines do not have a line number any more
    //so reference them by their old line number
    lines: 16-18
    old lines: 5-6
    comment by John Doe: Should not delete Lines 5 and 6.
    
    //discussions are simply comments one below the other
    comment by Daisy Delete: Why? Are they ever used somewhere?  
		And by the way. What's with lines 16-18?
	
	
    //--------------------------------
	file: path/nextfile.md
    //--------------------------------
	
	// ... and so on ...
    