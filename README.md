

### What is this project?

A coding challenge issued to evaluate how a candidate approaches software development.

### How do I use this project?

Getting started

1. Prerequistes: You have PHP version 8.0 or greater installed and configured
2. Download repository: `git clone https://github.com/brighamheyn/XESCodeChallege.git`
3. Navigate to project directory `cd XESCodeChallege`
4. Start php http server `php -S localhost:8000`
5. Navigate to http://localhost:8000/ in web browser

Ok, I am looking at the home page. Now what?

1. Enter a search term into the input
2. Click "Search" button to view the results 


### FAQ


**Is this solution over-engineered? And if so, why?**

**Yes.** What you are looking at here is almost certainly *not* the solution I would produce if someone came to me on a random Wednesday and said they needed a page to search for countries. The MVP I started with was a single script and ~20 LoC, and when you can solve a problem in 20 LoC you better have a good reason to add additional abstraction. 

Well in this case I do! I match solutions to problems. And one of the problems I want to address with this solution is how I can exemplify a deep understanding of software design. It's difficult to do that applied to a trivial problem without seeming over-built.

I would be happy to discuss how I arrived at the solution herein and why.

**No JS?**

**That's right!** I'll cut to the chase. It would have taken additional time (and code) to incorporate JS into my solution. This is a selfish optimization. 

**How are the files organized and why?**

**~/src** - Modules abstracting "layers" containing our application logic

Could I have implemented my own PSR-4 autoloader to match namespaces to files instead of modules and "includes"? **Yes.** Ultimately, though, that is just going to add additional "plumbing" code that obfuscates the solution. Additionally applying "includes" to the entry point *only* requires an acyclic composition of logical dependencies, and therefore has additional demonstrative power.

**~/index.php** - The entry point. A script *orchestrating* our logic and connecting it to our view. 

Could I have wired request paths to some controller method and created/managed output buffers to separated/untagle the backend logic from the view logic? **Yes.** This is one of the major problems solved by PHP frameworks and I don't want to write a framework. 

**Did you consider alternate data access patterns?**

**Yes.** Given the API there are 2 approaches: 

1. Use the "all" endpoint to get the full list of countries, and implement my own search algorithm
    * https://restcountries.com/v3.1/all (171 kB) 
    * https://restcountries.com/v3.1/all?fields=name,population,region,subregion,currencies,flags (28 kB)

2. Use the built-in search endpoints and implement deduping

There are numerous pros/cons to each regarding extensibility, optimization, caching, etc. which I would be happy to discuss. 

**Are we handling special characters?**
    * جمهورية كولومبيا
    * 哥伦比亚共和国

**Nope!** Character encodings are Vietnam. This is the opitome of the 80/20 rule. Chuck this into the "known limitations" column unless absolutely required.

**Searches on country code don't work for partial matches?**
    * https://restcountries.com/v3.1/alpha/620
    * https://restcountries.com/v3.1/alpha/62 (404)

**Unfortunate.** But I had already decided to offload the search algorthm to the client. Another "known limitation".
