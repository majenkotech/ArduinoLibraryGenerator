%LICENSE%
#include <%LIBNAME%.h>

// This is a generic constructor.  Expand as needed.  Constructors
// don't have a return type.
%LIBNAME%::%LIBNAME%() {
}

// Initialize any hardware here, not in the constructor.  You cannot
// guarantee the execution order of constructors, but you can guarantee
// when the begin member function is executed.
void %LIBNAME%::begin() {
}
