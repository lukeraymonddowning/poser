# TODO
Here you'll find ideas, suggestions, musings and the like regarding changes, additions and
improvements in the pipeline for Poser. Not all of them will make the final cut, but if they're
here then at least they won't be lost in the void of bad memory.

If you'd like to help out, feel free to grab one of these ideas, play around with it submit a 
pull request!

## Error Suggestions 

Provide typo suggestions in the error message when a user
types the incorrect relationship method name. For example, if a user typed `withCstomers()`, Poser
would suggest `did you mean withCustomers()?` in the error message.  

## Relationship Classes - completed in 3.0.0-beta

At the moment, relationships are handled as an array of arrays. Something like this:
`[['relationshipName', $data]]`. I'm not a huge fan of this. It doesn't feel right. I would
much prefer a `Relationship` class that holds the relationship name and data as parameters.

This might also pave the way for smarter `Relationship` classes that can behave in different ways 
dependant on the request. 

## Static method instantiation - completed in 2.8.0-beta

Similar to Laravel Facades, it would be good to be able to jump right into method calls instead of having to call `::new()`.
For example: `UserFactory::withCustomers(CustomerFactory::withBooks(5))()`.
