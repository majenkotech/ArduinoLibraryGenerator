%LICENSE%
#ifndef _%LIBCAP%_H
#define _%LIBCAP%_H

#if (ARDUINO >= 100) 
# include <Arduino.h>
#else
# include <WProgram.h>
#endif

class %LIBNAME% {
    private:
        // Private functions and variables here.  They can only be accessed
        // by functions within the class.

    public:
        // Public functions and variables.  These can be accessed from
        // outside the class.
        %LIBNAME%();
        void begin();
};
#endif
