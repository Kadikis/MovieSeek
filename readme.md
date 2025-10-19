# Movie Seek

_Sample project by Karlis Paegle_

Overall project could be improved by fetching data in the background, since OMDb API only returns 10 item pages, but that introduces problem of showing response to user in real time. Maybe could push web socket event on success on BE.

Searches attached to the sessions so they expire after two hours of inactivity. Should add task for cleaning up expired searches from DB.

## Steps for running this project

All commands executed in working directory.

1. Run command `composer setup`
2. Set value of OMDB_API_KEY in .env
3. Run project by executing `composer run dev`
