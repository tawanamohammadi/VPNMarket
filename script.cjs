const fs = require('fs');

let rawData = fs.readFileSync('apidoc.json');
let text = rawData.toString('utf8');
if (text.includes('\u0000')) {
    text = rawData.toString('utf16le');
}

const data = JSON.parse(text);

const schemas = [
    'LoginRequestDto',
    'LoginResponseDto',
    'CreateUserRequestDto',
    'UpdateUserRequestDto',
    'DeleteUserByUuidResponseDto',
    'ResetUserTrafficResponseDto',
    'DisableUserResponseDto',
    'EnableUserResponseDto',
    'CreateUserResponseDto'
];

const result = {};
for (const schema of schemas) {
    if (data.components && data.components.schemas && data.components.schemas[schema]) {
        result[schema] = data.components.schemas[schema];
    } else {
        result[schema] = 'Not found';
    }
}

fs.writeFileSync('schemas_resolved.json', JSON.stringify(result, null, 2), 'utf8');
