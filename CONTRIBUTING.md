# Contributing

Contributions are most welcome! 

## Commit messages

Keep your lines below 100 characters so they show up nicely on Github.  
Make sure you describe the new feature, or bugfix, or whatever. Please do not use commit messages
like "various tweaks". Ideally, `git log` reads like a changelog.

The latter can be helped by amending existing commits. Instead of 50 little commits with very
detailed messages, consider squashing commits into 1 or 2 separate commits with meaningful messages.

## Coding standards

We use PHPCS to check coding standards. There's a custom set of rules in `phpcs.xml`. It's a lot
more forgiving than other styles out there, so it shouldn't be that hard to comply. 

See also [our PHP CodeSniffer wiki page](https://github.com/grrr-amsterdam/garp3/wiki/PHP-CodeSniffer) 
for a way to automatically check coding standards before committing your work.

## Tests

Try to include tests if possible. `composer test` will run the tests using our configuration.  
The directory structure in `tests` mimics that outside it so we can easily see which files are
tested by the test class.  
