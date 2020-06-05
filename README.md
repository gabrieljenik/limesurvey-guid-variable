# GuidVariable
Adds a GUID variable to expression manager to be used on question text, help and answers.

Expected to be used as prefiller for an equation question.
The question will be prefilled with a good random string which can be used as a guid for the response and later be included in any expression manager sentence.

## Usage
An example usage is as follows:
1- On the plugin settings, at survey level, set which is going to be the name of the replacement variable.
   Ex: _guid
2- Create a short text question.
   Hide it if you need to by adding the "hide" class to the question.
3- Make its default answer to be [_guid]
